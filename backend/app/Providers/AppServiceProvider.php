<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Enrollment;
use App\Observers\EnrollmentObserver;

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
        Enrollment::observe(EnrollmentObserver::class);
    }
}
