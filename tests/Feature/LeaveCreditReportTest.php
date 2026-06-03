<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RequestCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveCreditReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('leave-credits.view', 'web');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('leave-credits.view');
    }

    public function test_leave_credit_roster_shows_all_employee_balances(): void
    {
        $department = Department::create(['name' => 'People & Culture']);
        $employee = User::factory()->create([
            'name' => 'Jane Employee',
            'preferred_name' => 'Jane',
            'email' => 'jane@example.com',
            'department_id' => $department->id,
            'position' => 'Coordinator',
        ]);

        RequestCredit::create([
            'user_id' => $employee->id,
            'pto' => 12.5,
            'wfh' => 6,
        ]);

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index'))
            ->assertOk()
            ->assertSee('Leave Credits')
            ->assertSee('Jane')
            ->assertSee('People &amp; Culture', false)
            ->assertSee('12.50')
            ->assertSee('6.00');
    }

    public function test_leave_credit_csv_download_is_available(): void
    {
        $employee = User::factory()->create(['name' => 'Jane Employee', 'email' => 'jane@example.com']);
        RequestCredit::create(['user_id' => $employee->id, 'pto' => 10, 'wfh' => 4]);

        $response = $this->actingAs($this->admin)
            ->get(route('leave-credits.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Jane Employee', $csv);
        $this->assertStringContainsString('Leave Credits', $csv);
        $this->assertStringContainsString('10.00', $csv);
    }

    public function test_leave_credit_pdf_download_is_available(): void
    {
        $employee = User::factory()->create(['name' => 'Jane Employee', 'email' => 'jane@example.com']);
        RequestCredit::create(['user_id' => $employee->id, 'pto' => 10, 'wfh' => 4]);

        $response = $this->actingAs($this->admin)
            ->get(route('leave-credits.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_leave_credit_roster_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('leave-credits.index'))
            ->assertForbidden();
    }
}
