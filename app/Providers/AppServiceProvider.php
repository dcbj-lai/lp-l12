<?php

namespace App\Providers;

// use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use OpenAI;
use OpenAI\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return OpenAI::client(config('services.openai.key'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('is-pnc', fn($user) => $user->hasAnyLegacyRole(['pnc.staff', 'pnc.admin', 'super.admin']));
        Gate::define('is-acad-admin', fn($user) => $user->hasAnyLegacyRole(['acad.admin', 'super.admin']));
        Gate::define('is-admin', fn($user) => $user->hasAnyLegacyRole(['sys.admin', 'super.admin']));
        Gate::define('is-finance', fn($user) => $user->hasAnyLegacyRole(['finance.admin', 'super.admin']));
        Gate::define('pnc-admin', fn($user) => $user->hasAnyLegacyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-manager-or-hr', fn($user) => $user->isManager() || $user->hasAnyLegacyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-super-admin', fn($user) => $user->hasAnyLegacyRole(['super.admin']));
        Gate::define('is-frontdesk', fn($user) => $user->hasAnyLegacyRole(['frontdesk.staff', 'super.admin']));
        Gate::define('is-comms-admin', fn($user) => $user->hasAnyLegacyRole(['comms.admin', 'super.admin']));
        Gate::define('guidance', fn($user) => $user->hasAnyLegacyRole(['guidance.admin']));
        Gate::define('is-clinic', fn($user) => $user->hasAnyLegacyRole(['clinic.admin']));


        // GoogleCredentialLoader::load();
    }
}
