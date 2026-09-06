<?php

use App\Http\Controllers\OylBoardController;
use App\Http\Controllers\OylCreatedPageController;
use App\Http\Controllers\OylRecipientPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Oyl/CreateChallenge', [
    'defaultDeliveryDate' => today()->addYear()->toDateString(),
    'maxVideoSizeMb' => (int) round(config('one-year-later.max_video_size_kb') / 1024),
    'priceCents' => (int) config('one-year-later.price_cents'),
]))->name('oyl.create');

Route::get('/challenge-created', [OylCreatedPageController::class, 'show'])
    ->name('oyl.created');

Route::get('/board', [OylBoardController::class, 'index'])->name('oyl.board');

Route::get('/p/{slug}', [OylBoardController::class, 'show'])
    ->where('slug', '[a-z0-9]{6,16}')
    ->name('oyl.public');

Route::get('/r/{token}/acknowledge', [OylRecipientPageController::class, 'acknowledge'])
    ->middleware('signed')
    ->name('oyl.recipient.acknowledge');

Route::get('/r/{token}/delivery', [OylRecipientPageController::class, 'delivery'])
    ->middleware('signed')
    ->name('oyl.recipient.delivery');
