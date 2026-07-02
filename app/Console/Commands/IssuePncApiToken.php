<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IssuePncApiToken extends Command
{
    protected $signature = 'users:issue-pnc-api-token
        {email : User email}
        {--name=pnc-backfill : Token name}
        {--expires= : Optional expiration date/time parsable by Carbon}
        {--revoke-existing : Revoke existing tokens with the same name first}';

    protected $description = 'Issue a Sanctum API token with P&C user and leave-credit access';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return Command::FAILURE;
        }

        $permissions = [
            'users.list',
            'users.edit',
            'requests.hr.view',
            'leave-credits.view',
            'leave-credits.assign',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['user', 'pnc.admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $user->assignRole(['user', 'pnc.admin']);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tokenName = (string) $this->option('name');

        if ($this->option('revoke-existing')) {
            $user->tokens()->where('name', $tokenName)->delete();
        }

        $expiresAt = $this->option('expires')
            ? now()->parse($this->option('expires'))
            : now()->addDay();

        $token = $user->createToken($tokenName, [
            'users:list',
            'users:edit',
            'leave-requests:view',
            'leave-credits:view',
            'leave-credits:assign',
        ], $expiresAt);

        $this->info('API token created. Copy this now; it will not be shown again.');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line("Expires: {$expiresAt->toDateTimeString()}");

        return Command::SUCCESS;
    }
}
