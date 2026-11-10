<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
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
        // اتفاقية الـ API: كل التواريخ تُرجع بصيغة ISO 8601 (SPEC 0.4).
        Carbon::serializeUsing(fn ($date) => $date->toIso8601String());

        // اتفاقية الـ pagination: ?page=1&per_page=12 (SPEC 0.4) —
        // الرد JSON بيتعمل عبر ApiResponse::apiPaginated().
        Paginator::useBootstrapFive();
    }
}
