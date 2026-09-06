<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\OylChallenge;
use App\Services\ChallengeActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class OylCreatedPageController extends Controller
{
    public function show(
        Request $request,
        PaymentGateway $payments,
        ChallengeActivation $activation,
    ): InertiaResponse {
        $sessionId = (string) $request->query('session_id', '');
        $challenge = $sessionId !== ''
            ? OylChallenge::where('stripe_session_id', $sessionId)->first()
            : null;

        if (! $challenge) {
            return Inertia::render('Oyl/Created', ['state' => 'unknown']);
        }

        if ($challenge->payment_status !== 'paid') {
            try {
                $session = $payments->getCheckoutSession($sessionId);
                if ($session['paid']) {
                    $activation->activate($challenge, $session['amount_total'], $session['currency']);
                }
            } catch (Throwable $exception) {
                Log::error('Could not verify Stripe checkout session.', [
                    'challenge_id' => $challenge->id,
                    'exception' => $exception,
                ]);
            }
        }

        if ($challenge->payment_status !== 'paid') {
            return Inertia::render('Oyl/Created', ['state' => 'unpaid']);
        }

        return Inertia::render('Oyl/Created', [
            'state' => 'sealed',
            'challenge' => [
                'goal_title' => $challenge->goal_title,
                'mode' => $challenge->mode,
                'notify_recipient' => (bool) $challenge->notify_recipient,
                'recipient_email' => $challenge->recipient_email,
                'recipient_name' => $challenge->recipient_name,
                'delivery_date' => $challenge->delivery_date->toDateString(),
                'amount_cents' => $challenge->amount_cents,
                'public_url' => $challenge->is_public ? $challenge->publicUrl() : null,
            ],
        ]);
    }
}
