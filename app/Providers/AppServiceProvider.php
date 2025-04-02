<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('manage-leaves', fn(User $user) => $user->hasAnyRole(['Manager', 'PNC']));
        Gate::define('is-pnc', fn($user) => $user->hasAnyRole(['PNC']));
        Gate::define('is-admin', fn($user) => $user->hasAnyRole(['Admin']));
        Gate::define('is-finance', fn($user) => $user->hasAnyRole(['Finance']));
        Gate::define('pin-ripple', fn($user) => in_array($user->role, ['Admin', 'PNC']));
        Gate::define('pnc-admin', fn($user) => $user->isPNCdmin());
    }
}
