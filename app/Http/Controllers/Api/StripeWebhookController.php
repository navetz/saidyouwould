<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\OylChallenge;
use App\Services\ChallengeActivation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $payments,
        ChallengeActivation $activation,
    ): JsonResponse {
        $payload = $request->getContent();
        abort_unless(
            $payments->verifyWebhook($payload, $request->header('Stripe-Signature', '')),
            400,
            'Invalid webhook signature.',
        );

        $event = json_decode($payload, true);

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            if (($session['payment_status'] ?? '') === 'paid' && isset($session['id'])) {
                $challenge = OylChallenge::where('stripe_session_id', $session['id'])->first();
                if ($challenge) {
                    $activation->activate(
                        $challenge,
                        $session['amount_total'] ?? null,
                        $session['currency'] ?? null,
                    );
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
