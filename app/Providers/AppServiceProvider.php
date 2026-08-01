<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production') && config('drsam.review_passwordless')) {
            throw new RuntimeException('DRSAM_REVIEW_PASSWORDLESS must be disabled in production.');
        }
    }
}
