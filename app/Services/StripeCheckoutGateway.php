<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\OylChallenge;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeCheckoutGateway implements PaymentGateway
{
    private const API_BASE = 'https://api.stripe.com/v1';

    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    public function createCheckoutSession(OylChallenge $challenge, string $successUrl, string $cancelUrl): array
    {
        $description = $challenge->mode === 'self'
            ? 'A sealed video for future you, delivered '.$challenge->delivery_date->format('F j, Y').'.'
            : 'A sealed video, delivered '.$challenge->delivery_date->format('F j, Y').'.';

        $session = $this->request()->post(self::API_BASE.'/checkout/sessions', [
            'mode' => 'payment',
            'customer_email' => $challenge->sender_email,
            'client_reference_id' => (string) $challenge->id,
            'metadata[challenge_id]' => (string) $challenge->id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => config('one-year-later.currency'),
            'line_items[0][price_data][unit_amount]' => config('one-year-later.price_cents'),
            'line_items[0][price_data][product_data][name]' => 'Said You Would',
            'line_items[0][price_data][product_data][description]' => $description,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ])->throw()->json();

        if (! isset($session['id'], $session['url'])) {
            throw new RuntimeException('Stripe did not return a usable checkout session.');
        }

        return ['id' => $session['id'], 'url' => $session['url']];
    }

    public function getCheckoutSession(string $sessionId): array
    {
        $session = $this->request()
            ->get(self::API_BASE.'/checkout/sessions/'.urlencode($sessionId))
            ->throw()
            ->json();

        return [
            'paid' => ($session['payment_status'] ?? null) === 'paid',
            'amount_total' => $session['amount_total'] ?? null,
            'currency' => $session['currency'] ?? null,
        ];
    }

    public function verifyWebhook(string $payload, string $signatureHeader): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        $timestamp = 0;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp <= 0 || abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS || $signatures === []) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured. Set STRIPE_SECRET in .env.');
        }

        return Http::withToken($secret)->asForm()->timeout(20);
    }
}
