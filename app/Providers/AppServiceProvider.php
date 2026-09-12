<?php

namespace App\Providers;

use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SecureFiles\FileSecurityScanner;
use App\Services\SecureFiles\NullFileSecurityScanner;
use App\Testing\TestDatabaseGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FileSecurityScanner::class, NullFileSecurityScanner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            TestDatabaseGuard::assertSafe();
        }

        Relation::enforceMorphMap([
            'student' => Student::class,
            'teacher' => Teacher::class,
            'parent' => AcademyParent::class,
            'user' => User::class,
        ]);

        $this->configureApiRateLimiting();

        Route::bind('parent', function (string $value): AcademyParent {
            return AcademyParent::query()->findOrFail($value);
        });
    }

    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api-login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute((int) config('aub.api.login_max_attempts_per_minute', 5))
                ->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api-mobile', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute((int) config('aub.api.authenticated_max_attempts_per_minute', 120))
                ->by($userId !== null ? 'user:'.$userId : 'ip:'.$request->ip());
        });
    }
}
