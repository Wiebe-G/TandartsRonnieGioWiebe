<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Treatment;
use App\Models\User;
use App\Observers\ActivityObserver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->logActivity();
    }

    /**
     * Keep track of what users do, for the admin log page.
     */
    protected function logActivity(): void
    {
        foreach ([Appointment::class, Invoice::class, Treatment::class, User::class] as $model) {
            $model::observe(ActivityObserver::class);
        }

        Event::listen(function (Login $event) {
            if ($event->user instanceof User) {
                ActivityLog::record('login', 'Ingelogd', user: $event->user);
            }
        });
        Event::listen(function (Logout $event) {
            if ($event->user instanceof User) {
                ActivityLog::record('logout', 'Uitgelogd', user: $event->user);
            }
        });
        Event::listen(fn (Failed $event) => ActivityLog::record(
            'login_failed',
            'Mislukte inlogpoging voor '.($event->credentials['email'] ?? 'onbekend'),
            user: $event->user instanceof User ? $event->user : null,
        ));
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
