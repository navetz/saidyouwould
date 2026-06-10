<?php

namespace App\Contracts;

use App\Models\OylChallenge;

interface ChallengeMailer
{
    public function sendImmediateNotice(OylChallenge $challenge, string $recipientToken): bool;

    public function sendSealedReceipt(OylChallenge $challenge): bool;

    public function sendFutureDelivery(OylChallenge $challenge, string $recipientToken): bool;
}
