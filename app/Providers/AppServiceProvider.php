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
        Gate::define('is-pnc', fn($user) => $user->hasAnyRole(['pnc.staff','pnc.admin','super.admin']));
        Gate::define('is-acad-admin', fn($user) => $user->hasAnyRole(['acad.admin','super.admin']));
        Gate::define('is-admin', fn($user) => $user->hasAnyRole(['sys.admin','super.admin']));
        Gate::define('is-finance', fn($user) => $user->hasAnyRole(['finance.admin','super.admin']));
        Gate::define('pnc-admin', fn($user) => $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-manager-or-hr', fn($user) => $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-manager-or-hr', fn ($user) => $user->isManager() || $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-super-admin', fn($user) => $user->hasAnyRole(['super.admin']));
        Gate::define('is-frontdesk', fn($user) => $user->hasAnyRole(['frontdesk.staff','super.admin']));
        Gate::define('guidance', fn ($user) => $user->hasAnyRole(['guidance.staff', 'guidance.admin', 'super.admin']));
        Gate::define('guidance-admin', fn ($user) =>$user->hasAnyRole(['guidance.admin', 'super.admin']));


        try {
        $path = \App\Helpers\GoogleCredentialHelper::ensureCredentialsFile();
        config(['google-calendar.service_account_credentials_json' => $path]);
    } catch (\Throwable $e) {
        \Log::error('Failed to load Google Calendar credentials: ' . $e->getMessage());
    }
    }
}
