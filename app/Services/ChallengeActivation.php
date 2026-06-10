<?php

namespace App\Services;

use App\Contracts\ChallengeMailer;
use App\Models\OylChallenge;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ChallengeActivation
{
    public function __construct(private ChallengeMailer $mailer)
    {
    }

    /**
     * Mark a challenge paid and send the kickoff emails. Safe to call from both
     * the Stripe webhook and the success page; only the first call acts.
     */
    public function activate(OylChallenge $challenge, ?int $amountCents, ?string $currency): void
    {
        $activated = DB::transaction(function () use ($challenge, $amountCents, $currency) {
            $fresh = OylChallenge::whereKey($challenge->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->payment_status === 'paid') {
                return null;
            }

            $fresh->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'amount_cents' => $amountCents,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            return $fresh;
        });

        if (! $activated) {
            $challenge->refresh();

            return;
        }

        if ($activated->mode === 'friend' && $activated->notify_recipient) {
            $token = Crypt::decryptString($activated->recipient_token_encrypted);
            $this->mailer->sendImmediateNotice($activated, $token);
        }

        $this->mailer->sendSealedReceipt($activated);
        $challenge->refresh();
    }
}
