<?php

namespace App\Providers;

use App\Listeners\AuditLogSubscriber;
use App\Listeners\WebhookSubscriber;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Volt;

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
        $this->configureDefaults();

        Volt::mount([resource_path('views/pages')], 'pages');

        Event::subscribe(AuditLogSubscriber::class);
        Event::subscribe(WebhookSubscriber::class);

        RateLimiter::for('api-client', function (Request $request) {
            $client = $request->attributes->get('api_client');
            $key = $client ? 'api-client:'.$client->id : 'api-client:anonymous';

            return Limit::perMinute($client?->rate_limit ?? 60)->by($key);
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
