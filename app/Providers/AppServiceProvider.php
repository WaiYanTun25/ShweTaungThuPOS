<?php

namespace App\Providers;

use App\Models\Issue;
use App\Models\Receive;
use App\Observers\ReceiveObserver;
use App\Observers\TransferObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // observers for models
        Issue::observe(TransferObserver::class);
        Receive::observe(ReceiveObserver::class);
    }
}
