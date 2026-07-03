<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_pnc_admin_sees_my_approvals_navigation(): void
    {
        Role::findOrCreate('pnc.admin', 'web');

        $user = User::factory()->create([
            'email' => 'perly.gonzales@life.edu.ph',
        ]);
        $user->assignRole('pnc.admin');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My Approvals')
            ->assertSee('Schedule Requests');
    }
}
