<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers()->uncompromised());

        // Platform-wide super admin bypasses every ability check.
        Gate::before(function ($user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
