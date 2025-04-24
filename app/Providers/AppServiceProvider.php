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
        Gate::define('manage-leaves', fn(User $user) => $user->hasAnyRole(['pnc.admin']));
        Gate::define('is-pnc', fn($user) => $user->hasAnyRole(['pnc.staff','super.admin']));
        Gate::define('is-admin', fn($user) => $user->hasAnyRole(['sys.admin','super.admin']));
        Gate::define('is-finance', fn($user) => $user->hasAnyRole(['finance.admin','super.admin']));
        Gate::define('pin-ripple', fn($user) => in_array($user->role, ['sys.admin', 'super.admin']));
        Gate::define('pnc-admin', fn($user) => $user->hasAnyRole(['pnc.admin', 'super.admin']));
    }
}
