<?php

namespace App\Http\Controllers\Api;

use App\Contracts\VideoStorage;
use App\Http\Controllers\Controller;
use App\Models\OylChallenge;
use App\Models\OylRecipientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OylRecipientController extends Controller
{
    public function acknowledge(string $token): JsonResponse
    {
        $challenge = $this->challenge($token);
        $challenge->update(['acknowledged_at' => $challenge->acknowledged_at ?? now()]);

        return response()->json(['message' => 'Acknowledged.']);
    }

    public function respond(Request $request, string $token): JsonResponse
    {
        $challenge = $this->challenge($token);
        abort_unless($challenge->status === 'delivered', 403, 'The video has not been delivered yet.');

        $validated = $request->validate([
            'response' => ['required', Rule::in(['did_it', 'didnt_do_it'])],
            'response_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $response = OylRecipientResponse::updateOrCreate(
            ['challenge_id' => $challenge->id],
            $validated,
        );

        return response()->json([
            'message' => 'Your answer has been recorded.',
            'response' => $response->only(['response', 'response_note']),
        ]);
    }

    public function video(string $token, VideoStorage $videos): RedirectResponse
    {
        $challenge = $this->challenge($token);
        abort_unless($challenge->status === 'delivered', 403);

        return redirect()->away($videos->temporaryUrl($challenge->video_storage_key));
    }

    private function challenge(string $token): OylChallenge
    {
        return OylChallenge::where('recipient_token_hash', hash('sha256', $token))->firstOrFail();
    }
}
