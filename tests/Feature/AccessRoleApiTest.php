<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessRoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_admin_can_assign_sync_and_revoke_user_roles(): void
    {
        $accessAdminRole = Role::findOrCreate('access.admin', 'web');
        Role::findOrCreate('pnc.super', 'web');
        Role::findOrCreate('facility.admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole($accessAdminRole);

        $target = User::factory()->create([
            'employee_number' => '20260001',
            'name' => 'Role Target',
            'email' => 'target@example.com',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('access.api.roles.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'pnc.super']);

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('users.api.roles.assign', $target), [
                'role' => 'pnc.super',
            ])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonFragment(['name' => 'pnc.super']);

        $this->assertTrue($target->fresh()->hasRole('pnc.super'));

        $this->actingAs($admin, 'sanctum')
            ->putJson(route('users.api.roles.sync', $target), [
                'roles' => ['facility.admin'],
            ])
            ->assertOk()
            ->assertJsonFragment(['name' => 'facility.admin'])
            ->assertJsonMissing(['name' => 'pnc.super']);

        $target->refresh();
        $this->assertTrue($target->hasRole('facility.admin'));
        $this->assertFalse($target->hasRole('pnc.super'));

        $this->actingAs($admin, 'sanctum')
            ->deleteJson(route('users.api.roles.revoke', [$target, 'facility.admin']))
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('user.roles', []);

        $this->assertFalse($target->fresh()->hasRole('facility.admin'));
    }

    public function test_role_assignment_api_requires_access_admin_role(): void
    {
        Role::findOrCreate('pnc.super', 'web');

        $plainUser = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($plainUser, 'sanctum')
            ->postJson(route('users.api.roles.assign', $target), [
                'role' => 'pnc.super',
            ])
            ->assertForbidden();
    }
}
