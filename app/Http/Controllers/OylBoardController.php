<?php

namespace App\Http\Controllers;

use App\Contracts\VideoStorage;
use App\Models\OylChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OylBoardController extends Controller
{
    public function index(): InertiaResponse
    {
        $upcoming = OylChallenge::onBoard()
            ->where('status', 'pending')
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->limit(200)
            ->get();

        $landed = OylChallenge::onBoard()
            ->with('response')
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->limit(100)
            ->get();

        return Inertia::render('Oyl/Board', [
            'upcoming' => $upcoming->map->boardData()->values(),
            'landed' => $landed->map->boardData()->values(),
            'priceCents' => (int) config('one-year-later.price_cents'),
        ]);
    }

    public function show(string $slug): InertiaResponse|Response
    {
        $challenge = $this->find($slug);

        if (! $challenge) {
            return Inertia::render('Oyl/LinkError', ['message' => 'That one is not on the board.'])
                ->toResponse(request())
                ->setStatusCode(404);
        }

        return Inertia::render('Oyl/Public', [
            'challenge' => $challenge->boardData(),
            'videoUrl' => $challenge->status === 'delivered' ? url('/api/public/'.$slug.'/video') : null,
            'priceCents' => (int) config('one-year-later.price_cents'),
        ]);
    }

    public function video(string $slug, VideoStorage $videos): RedirectResponse
    {
        $challenge = $this->find($slug);
        abort_unless($challenge && $challenge->status === 'delivered', 404);

        return redirect()->away($videos->temporaryUrl($challenge->video_storage_key));
    }

    private function find(string $slug): ?OylChallenge
    {
        return OylChallenge::onBoard()->with('response')->where('public_slug', $slug)->first();
    }
}
