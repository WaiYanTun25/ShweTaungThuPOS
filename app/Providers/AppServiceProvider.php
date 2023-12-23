<?php

namespace App\Providers;

use App\Models\Transfer;
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
        Transfer::observe(TransferObserver::class);
    }
}
