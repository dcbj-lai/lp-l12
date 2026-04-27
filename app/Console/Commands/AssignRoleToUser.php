<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRoleToUser extends Command
{
    protected $signature = 'user:assign-role 
                            {email : The user email} 
                            {role : The role to assign}';

    protected $description = 'Assign a role to a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return self::FAILURE;
        }

        // Ensure role exists
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Role does not exist: {$roleName}");
            return self::FAILURE;
        }

        // Assign role
        if ($user->hasRole($roleName)) {
            $this->info("User already has role: {$roleName}");
            return self::SUCCESS;
        }

        $user->assignRole($roleName);

        $this->info("✅ Role '{$roleName}' assigned to {$email}");

        return self::SUCCESS;
    }
}
