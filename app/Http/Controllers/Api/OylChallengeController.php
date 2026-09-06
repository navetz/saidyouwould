<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGateway;
use App\Contracts\VideoStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOylChallengeRequest;
use App\Models\OylChallenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OylChallengeController extends Controller
{
    public function store(
        StoreOylChallengeRequest $request,
        VideoStorage $videos,
        PaymentGateway $payments,
    ): JsonResponse {
        $validated = $request->validated();
        $storageKey = $validated['video_storage_key'];
        $videos->validateUploaded($storageKey, $validated['video_upload_token']);
        $recipientToken = Str::random(64);

        $isSelf = $validated['mode'] === 'self';

        try {
            $challenge = DB::transaction(fn () => OylChallenge::create([
                'sender_email' => $validated['sender_email'],
                'sender_name' => $validated['sender_name'] ?? null,
                'mode' => $validated['mode'],
                'recipient_email' => $isSelf ? $validated['sender_email'] : $validated['recipient_email'],
                'recipient_name' => $isSelf ? ($validated['sender_name'] ?? null) : ($validated['recipient_name'] ?? null),
                'notify_recipient' => $isSelf ? false : (bool) $validated['notify_recipient'],
                'anonymous' => $isSelf ? false : (bool) ($validated['anonymous'] ?? false),
                'is_public' => (bool) ($validated['is_public'] ?? false),
                'public_slug' => strtolower(Str::random(10)),
                'source' => isset($validated['source']) ? strtolower($validated['source']) : null,
                'goal_title' => $validated['goal_title'] ?? null,
                'goal_description' => $validated['goal_description'] ?? null,
                'written_terms' => $validated['written_terms'] ?? null,
                'video_storage_key' => $storageKey,
                'delivery_date' => $validated['delivery_date'],
                'status' => 'awaiting_payment',
                'payment_status' => 'unpaid',
                'recipient_token_hash' => hash('sha256', $recipientToken),
                'recipient_token_encrypted' => Crypt::encryptString($recipientToken),
            ]));

            $session = $payments->createCheckoutSession(
                $challenge,
                url('/challenge-created').'?session_id={CHECKOUT_SESSION_ID}',
                url('/').'?checkout=cancelled',
            );

            $challenge->update(['stripe_session_id' => $session['id']]);
        } catch (Throwable $exception) {
            if (! isset($challenge)) {
                $videos->delete($storageKey);
            }
            throw $exception;
        }

        return response()->json([
            'message' => 'Challenge saved. Complete the payment to seal it.',
            'checkout_url' => $session['url'],
            'challenge' => [
                'id' => $challenge->id,
                'goal_title' => $challenge->goal_title,
                'mode' => $challenge->mode,
                'delivery_date' => $challenge->delivery_date->toDateString(),
                'public_url' => $challenge->is_public ? $challenge->publicUrl() : null,
            ],
        ], 201);
    }
}
