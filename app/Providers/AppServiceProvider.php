<?php

namespace App\Providers;

use App\Models\AcademyParent;
use Illuminate\Support\Facades\Route;
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
        Route::bind('parent', function (string $value): AcademyParent {
            return AcademyParent::query()->findOrFail($value);
        });
    }
}
