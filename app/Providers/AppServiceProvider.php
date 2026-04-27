<?php

namespace App\Providers;

// use App\Models\User;
use App\Services\GoogleCredentialLoader;
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
        Gate::define('is-pnc', fn($user) => $user->hasAnyRole(['pnc.staff', 'pnc.admin', 'super.admin']));
        Gate::define('is-acad-admin', fn($user) => $user->hasAnyRole(['acad.admin', 'super.admin']));
        Gate::define('is-admin', fn($user) => $user->hasAnyRole(['sys.admin', 'super.admin']));
        Gate::define('is-finance', fn($user) => $user->hasAnyRole(['finance.admin', 'super.admin']));
        Gate::define('pnc-admin', fn($user) => $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-manager-or-hr', fn($user) => $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-manager-or-hr', fn($user) => $user->isManager() || $user->hasAnyRole(['pnc.admin', 'super.admin']));
        Gate::define('is-super-admin', fn($user) => $user->hasAnyRole(['super.admin']));
        Gate::define('is-frontdesk', fn($user) => $user->hasAnyRole(['frontdesk.staff', 'super.admin']));
        Gate::define('is-comms-admin', fn($user) => $user->hasAnyRole(['comms.admin', 'super.admin']));
        Gate::define('guidance', fn($user) => $user->hasAnyRole(['guidance.admin']));
        Gate::define('is-clinic', fn($user) => $user->hasAnyRole(['clinic.admin']));


        GoogleCredentialLoader::load();
    }
}
