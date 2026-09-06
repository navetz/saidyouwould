<?php

use App\Http\Controllers\Api\OylChallengeController;
use App\Http\Controllers\Api\OylRecipientController;
use App\Http\Controllers\Api\OylUploadController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:60,1');

Route::post('/uploads', [OylUploadController::class, 'store'])
    ->middleware('throttle:10,1');

Route::post('/challenges', [OylChallengeController::class, 'store'])
    ->middleware('throttle:5,1');

Route::prefix('/recipient/{token}')->middleware('throttle:20,1')->group(function () {
    Route::post('/acknowledge', [OylRecipientController::class, 'acknowledge']);
    Route::post('/response', [OylRecipientController::class, 'respond']);
    Route::get('/video', [OylRecipientController::class, 'video'])
        ->middleware('signed')
        ->name('api.oyl.recipient.video');
});

Route::get('/public/{slug}/video', [\App\Http\Controllers\OylBoardController::class, 'video'])
    ->where('slug', '[a-z0-9]{6,16}')
    ->middleware('throttle:60,1')
    ->name('api.oyl.public.video');
