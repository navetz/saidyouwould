<?php

namespace App\Http\Controllers;

use App\Models\OylChallenge;
use App\Services\ChallengeLinks;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OylRecipientPageController extends Controller
{
    public function acknowledge(string $token): InertiaResponse|Response
    {
        $challenge = $this->findChallenge($token);

        if (! $challenge) {
            return $this->error('This challenge link is no longer available.', 404);
        }

        return Inertia::render('Oyl/Recipient', [
            'mode' => 'acknowledge',
            'recipientToken' => $token,
            'challenge' => $this->challengeData($challenge),
        ]);
    }

    public function delivery(string $token, ChallengeLinks $links): InertiaResponse|Response
    {
        $challenge = $this->findChallenge($token);

        if (! $challenge) {
            return $this->error('This delivery link is no longer available.', 404);
        }

        if ($challenge->status !== 'delivered') {
            return $this->error('This video is still being kept for the future.', 403);
        }

        return Inertia::render('Oyl/Recipient', [
            'mode' => 'delivery',
            'recipientToken' => $token,
            'videoUrl' => $links->video($challenge, $token),
            'challenge' => $this->challengeData($challenge),
        ]);
    }

    private function findChallenge(string $token): ?OylChallenge
    {
        return OylChallenge::with('response')
            ->where('recipient_token_hash', hash('sha256', $token))
            ->first();
    }

    private function challengeData(OylChallenge $challenge): array
    {
        return [
            'sender_name' => $challenge->publicSenderName(),
            'anonymous' => (bool) $challenge->anonymous,
            'recipient_name' => $challenge->recipient_name,
            'mode' => $challenge->mode,
            'notify_recipient' => (bool) $challenge->notify_recipient,
            'goal_title' => $challenge->goal_title,
            'goal_description' => $challenge->goal_description,
            'written_terms' => $challenge->written_terms,
            'delivery_date' => $challenge->delivery_date->toDateString(),
            'acknowledged' => $challenge->acknowledged_at !== null,
            'response' => $challenge->response ? [
                'response' => $challenge->response->response,
                'response_note' => $challenge->response->response_note,
            ] : null,
        ];
    }

    private function error(string $message, int $status): Response
    {
        return Inertia::render('Oyl/LinkError', ['message' => $message])
            ->toResponse(request())
            ->setStatusCode($status);
    }
}
