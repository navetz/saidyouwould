<?php

namespace App\Services;

use App\Models\OylChallenge;
use Illuminate\Support\Facades\URL;

class ChallengeLinks
{
    public function acknowledgement(OylChallenge $challenge, string $token): string
    {
        $expiresAt = $challenge->delivery_date->copy()->endOfDay()->addDays(30);

        return URL::temporarySignedRoute('oyl.recipient.acknowledge', $expiresAt, ['token' => $token]);
    }

    public function delivery(OylChallenge $challenge, string $token): string
    {
        return URL::temporarySignedRoute(
            'oyl.recipient.delivery',
            now()->addDays(config('one-year-later.delivery_link_days')),
            ['token' => $token],
        );
    }

    public function video(OylChallenge $challenge, string $token): string
    {
        return URL::temporarySignedRoute(
            'api.oyl.recipient.video',
            now()->addHours(2),
            ['token' => $token],
        );
    }
}
