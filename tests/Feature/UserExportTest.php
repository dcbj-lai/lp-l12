<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('users.list');
    }

    public function test_users_index_shows_vcard_url_and_export_actions(): void
    {
        User::factory()->create([
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
            ->assertSee('https://lp.life.edu.ph/card/jane-employee');
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
