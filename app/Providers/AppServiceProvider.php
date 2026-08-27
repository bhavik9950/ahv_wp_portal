<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\CurrentOrganization;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->scoped(CurrentOrganization::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            $rule = Password::min(12)->letters()->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Platform-wide super admin bypasses every ability check.
        Gate::before(function ($user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by((string) $request->ip()),
        ]);
    }
}
