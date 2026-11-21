<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permission;
use App\Services\Bot\Contracts\LlmClient;
use App\Services\Bot\Llm\AnthropicLlmClient;
use App\Services\Bot\Llm\FakeLlmClient;
use App\Support\CurrentOrganization;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->scoped(CurrentOrganization::class);

        $this->app->bind(LlmClient::class, fn () => match ((string) config('services.bot.driver', 'anthropic')) {
            'fake' => new FakeLlmClient,
            default => new AnthropicLlmClient,
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);

        // Turbo Drive reloads the page when a deploy changes the asset hashes.
        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            $rule = Password::min(12)->letters()->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Indian Standard Time (IST) formatting macros
        Carbon::macro('toIndianDate', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Asia/Kolkata')->format('d M Y');
        });

        Carbon::macro('toIndianTime', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Asia/Kolkata')->format('h:i A');
        });

        Carbon::macro('toIndianDateTime', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Asia/Kolkata')->format('d M Y, h:i A');
        });

        Carbon::macro('toIndianFullDateTime', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone('Asia/Kolkata')->format('d/m/Y h:i:s A');
        });

        // Platform-wide super admin bypasses every ability check.
        Gate::before(function ($user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // The AI assistant is part of the WhatsApp integration config.
        Gate::define('manage-bot', fn ($user) => $user->can(Permission::WabaManage->value));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by((string) $request->ip()),
        ]);

        // Meta can burst webhooks; keep this high but bounded per source IP.
        RateLimiter::for('whatsapp-webhook', fn (Request $request) => Limit::perMinute(600)->by((string) $request->ip()));
    }
}
