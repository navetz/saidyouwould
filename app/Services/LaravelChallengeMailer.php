<?php

namespace App\Services;

use App\Contracts\ChallengeMailer;
use App\Mail\ChallengeSealedReceipt;
use App\Mail\FutureChallengeDelivery;
use App\Mail\ImmediateChallengeNotice;
use App\Models\OylChallenge;
use App\Models\OylEmailEvent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LaravelChallengeMailer implements ChallengeMailer
{
    public function __construct(private ChallengeLinks $links)
    {
    }

    public function sendImmediateNotice(OylChallenge $challenge, string $recipientToken): bool
    {
        return $this->send(
            $challenge,
            'immediate_notice',
            $challenge->recipient_email,
            new ImmediateChallengeNotice($challenge, $this->links->acknowledgement($challenge, $recipientToken)),
        );
    }

    public function sendSealedReceipt(OylChallenge $challenge): bool
    {
        return $this->send(
            $challenge,
            'sealed_receipt',
            $challenge->sender_email,
            new ChallengeSealedReceipt($challenge),
        );
    }

    public function sendFutureDelivery(OylChallenge $challenge, string $recipientToken): bool
    {
        return $this->send(
            $challenge,
            'future_delivery',
            $challenge->recipient_email,
            new FutureChallengeDelivery($challenge, $this->links->delivery($challenge, $recipientToken)),
        );
    }

    private function send(OylChallenge $challenge, string $type, string $to, Mailable $mailable): bool
    {
        $event = OylEmailEvent::create([
            'challenge_id' => $challenge->id,
            'email_type' => $type,
            'recipient_email' => $to,
            'status' => 'pending',
        ]);

        try {
            Mail::to($to)->send($mailable);
            $event->update(['status' => 'sent', 'sent_at' => now()]);

            return true;
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 5000),
            ]);
            Log::error('Said You Would email failed.', [
                'challenge_id' => $challenge->id,
                'email_type' => $type,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
