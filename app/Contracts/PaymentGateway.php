<?php

namespace App\Contracts;

use App\Models\OylChallenge;

interface PaymentGateway
{
    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(OylChallenge $challenge, string $successUrl, string $cancelUrl): array;

    /**
     * @return array{paid: bool, amount_total: int|null, currency: string|null}
     */
    public function getCheckoutSession(string $sessionId): array;

    public function verifyWebhook(string $payload, string $signatureHeader): bool;
}
