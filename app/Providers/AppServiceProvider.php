<?php

namespace App\Providers;

use App\Contracts\ChallengeMailer;
use App\Contracts\PaymentGateway;
use App\Contracts\VideoStorage;
use App\Services\LaravelChallengeMailer;
use App\Services\LaravelVideoStorage;
use App\Services\StripeCheckoutGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VideoStorage::class, LaravelVideoStorage::class);
        $this->app->bind(ChallengeMailer::class, LaravelChallengeMailer::class);
        $this->app->bind(PaymentGateway::class, StripeCheckoutGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
