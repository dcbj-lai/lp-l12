<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('users.list', 'web');
        Permission::findOrCreate('users.edit', 'web');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['users.list', 'users.edit']);
    }

    public function test_users_index_shows_vcard_url_and_export_actions(): void
    {
        User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Export PDF')
            ->assertSee('Vcard URL')
            ->assertSee('20250001')
            ->assertSee('https://lp.life.edu.ph/card/jane-employee');
    }

    public function test_users_index_defaults_to_active_users_and_can_filter_inactive_users(): void
    {
        User::factory()->create([
            'name' => 'Active Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Inactive Employee',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Active Employee')
            ->assertDontSee('Inactive Employee');

        $this->actingAs($this->admin)
            ->get(route('users.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Active Employee')
            ->assertSee('Inactive Employee');

        $this->actingAs($this->admin)
            ->get(route('users.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertDontSee('Active Employee')
            ->assertSee('Inactive Employee');
    }

    public function test_user_detail_can_mark_a_user_inactive(): void
    {
        $employee = User::factory()->create([
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('users.update', $employee), [
                'name' => 'Jane Employee',
                'email' => 'jane@example.com',
                'check_in_mode' => 'virtual',
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($employee->fresh()->is_active);
    }

    public function test_browser_authenticated_users_endpoint_returns_users(): void
    {
        User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'position' => 'Coordinator',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('users.api.index'))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'name' => 'Jane Employee',
                'email' => 'jane@example.com',
            ]);
    }

    public function test_users_api_defaults_to_active_users_and_can_include_all_statuses(): void
    {
        User::factory()->create([
            'name' => 'Active Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Inactive Employee',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('users.api.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Employee'])
            ->assertJsonMissing(['name' => 'Inactive Employee']);

        $this->actingAs($this->admin)
            ->getJson(route('users.api.index', ['status' => 'all']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Employee'])
            ->assertJsonFragment(['name' => 'Inactive Employee']);
    }

    public function test_users_csv_export_includes_vcard_url_and_excludes_payroll_on(): void
    {
        User::factory()->create([
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'payroll_on' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('users.export.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Jane Employee', $csv);
        $this->assertStringContainsString('Vcard URL', $csv);
        $this->assertStringContainsString('https://lp.life.edu.ph/card/jane-employee', $csv);
        $this->assertStringNotContainsString('Payroll On', $csv);
    }

    public function test_browser_authenticated_employee_number_backfill_endpoint_can_preview_and_apply(): void
    {
        $employee = User::factory()->create([
            'name' => 'Don Balbieran',
            'email' => 'don.balbieran@example.com',
        ]);

        $payload = [
            'employees' => [
                [
                    'name' => 'BALBIERAN JR, DELFIN, CHECON',
                    'employee_number' => 20230801,
                ],
            ],
        ];

        $this->actingAs($this->admin)
            ->postJson(route('users.api.employee-numbers.backfill'), $payload + ['dry_run' => true])
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('updated', 0)
            ->assertJsonPath('results.0.status', 'matched');

        $this->assertNull($employee->fresh()->employee_number);

        $this->actingAs($this->admin)
            ->postJson(route('users.api.employee-numbers.backfill'), $payload)
            ->assertOk()
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('results.0.status', 'updated');

        $this->assertSame('20230801', $employee->fresh()->employee_number);
    }

    public function test_sanctum_token_can_backfill_employee_numbers(): void
    {
        $employee = User::factory()->create([
            'name' => 'Don Balbieran',
            'email' => 'don.balbieran@example.com',
        ]);

        $token = $this->admin->createToken('test-pnc-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/users/employee-numbers/backfill', [
                'employees' => [
                    [
                        'name' => 'BALBIERAN JR, DELFIN, CHECON',
                        'employee_number' => 20230801,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('results.0.status', 'updated');

        $this->assertSame('20230801', $employee->fresh()->employee_number);
    }

    public function test_sanctum_token_can_update_user_avatar_by_email(): void
    {
        Storage::fake('s3');

        $employee = User::factory()->create([
            'name' => 'Edric Mendoza',
            'email' => 'edric.mendoza@example.com',
            'profile_photo_path' => 'avatars/999/old_avatar.jpg',
        ]);

        Storage::disk('s3')->put($employee->profile_photo_path, 'old');

        $token = $this->admin->createToken('test-user-avatar-token')->plainTextToken;
        $avatar = UploadedFile::fake()->image('avatar.jpg')->size(4096);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/users/avatar', [
                'email' => 'EDRIC.MENDOZA@example.com',
                'avatar' => $avatar,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('user.email', 'edric.mendoza@example.com');

        $employee->refresh();

        $this->assertNotSame('avatars/999/old_avatar.jpg', $employee->profile_photo_path);
        $this->assertStringStartsWith("avatars/{$employee->id}/avatar_", $employee->profile_photo_path);
        Storage::disk('s3')->assertMissing('avatars/999/old_avatar.jpg');
        Storage::disk('s3')->assertExists($employee->profile_photo_path);
    }

    public function test_users_pdf_export_is_available(): void
    {
        User::factory()->create([
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('users.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_user_exports_require_users_list_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.export.csv'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('users.export.pdf'))
            ->assertForbidden();
    }
}
