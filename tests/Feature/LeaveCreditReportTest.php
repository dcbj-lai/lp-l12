<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveReplenishmentRun;
use App\Models\LeaveReplenishmentRunItem;
use App\Models\OrgSetting;
use App\Models\Request as StaffRequest;
use App\Models\RequestCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        Permission::findOrCreate('leave-credits.assign', 'web');
        Permission::findOrCreate('leave-credits.initialize', 'web');
        Permission::findOrCreate('requests.hr.view', 'web');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['leave-credits.view', 'leave-credits.assign', 'requests.hr.view']);
    }

    public function test_leave_credit_roster_shows_all_employee_balances(): void
    {
        $department = Department::create(['name' => 'People & Culture']);
        $employee = User::factory()->create([
            'employee_number' => '20250001',
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

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Vacation',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-09',
            'end_date_type' => 'full',
            'number_of_days' => 2,
            'status' => 'approved',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'is_offset' => true,
            'reason' => 'Weekend event',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-26',
            ]))
            ->assertOk()
            ->assertSee('Leave Credits')
            ->assertSee('Date From')
            ->assertSee('Date To')
            ->assertDontSee('Starting Leave</p>', false)
            ->assertDontSee('Comp-Off</p>', false)
            ->assertSee('20250001')
            ->assertSee('Jane')
            ->assertSee('14.50')
            ->assertSee('2.00')
            ->assertSee('12.50')
            ->assertSee('1.00');
    }

    public function test_leave_credit_roster_defaults_to_active_users_and_can_filter_inactive_users(): void
    {
        User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Active Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'employee_number' => '20250002',
            'name' => 'Inactive Employee',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index'))
            ->assertOk()
            ->assertSee('Active Employee')
            ->assertDontSee('Inactive Employee');

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Active Employee')
            ->assertSee('Inactive Employee');

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertDontSee('Active Employee')
            ->assertSee('Inactive Employee');
    }

    public function test_leave_credit_search_is_case_insensitive(): void
    {
        $department = Department::create(['name' => 'People & Culture']);

        User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'department_id' => $department->id,
            'position' => 'Coordinator',
        ]);

        User::factory()->create([
            'employee_number' => '20250002',
            'name' => 'Other Employee',
            'email' => 'other@example.com',
            'position' => 'Assistant',
        ]);

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index', ['search' => 'jAnE']))
            ->assertOk()
            ->assertSee('Jane Employee')
            ->assertDontSee('Other Employee');

        $this->actingAs($this->admin)
            ->get(route('leave-credits.index', ['search' => 'people']))
            ->assertOk()
            ->assertSee('Jane Employee')
            ->assertDontSee('Other Employee');
    }

    public function test_leave_credit_api_report_returns_requested_columns(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Vacation',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('leave-credits.api.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-26',
            ]))
            ->assertOk()
            ->assertJsonPath('period.date_from', '2026-06-01')
            ->assertJsonPath('period.date_to', '2026-06-26')
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'employee_name' => 'Jane Employee',
                'starting_leave_credits' => 9,
                'total_leave_days_used_to_date' => 1,
                'leave_balance_to_date' => 8,
            ]);
    }

    public function test_leave_request_api_returns_raw_leave_requests_for_reconciliation(): void
    {
        $department = Department::create(['name' => 'People & Culture']);
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'department_id' => $department->id,
        ]);
        $approver = User::factory()->create([
            'name' => 'Pnc Approver',
            'email' => 'pnc@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'approver_id' => $approver->id,
            'type' => 'PTO',
            'reason' => 'Vacation',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
            'remarks' => 'Approved for audit',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Cancelled vacation',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('leave-requests.api.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'employee_name' => 'Jane Employee',
                'department' => 'People & Culture',
                'type' => 'PTO',
                'status' => 'approved',
                'number_of_days' => 1,
            ])
            ->assertJsonFragment([
                'status' => 'cancelled',
                'reason' => 'Cancelled vacation',
            ])
            ->assertJsonPath('data.1.current_credit_snapshot.pto', 8);
    }

    public function test_leave_request_api_can_filter_cancelled_requests(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Vacation',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Cancelled vacation',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('leave-requests.api.index', [
                'status' => 'cancelled',
                'employee_number' => '20250001',
            ]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.0.reason', 'Cancelled vacation');
    }

    public function test_sanctum_token_can_read_leave_requests(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Production pull test',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
        ]);

        $token = $this->admin->createToken('test-pnc-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/leave-requests?date_from=2026-06-01&date_to=2026-06-30')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'reason' => 'Production pull test',
                'status' => 'approved',
            ]);
    }

    public function test_cancelled_requests_are_exported_but_do_not_increase_leave_credit_usage(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Approved vacation',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'approved',
        ]);

        StaffRequest::create([
            'user_id' => $employee->id,
            'type' => 'PTO',
            'reason' => 'Cancelled vacation',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'end_date_type' => 'full',
            'number_of_days' => 1,
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('leave-credits.api.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'starting_leave_credits' => 9,
                'total_leave_days_used_to_date' => 1,
                'leave_balance_to_date' => 8,
            ]);
    }

    public function test_leave_credit_api_defaults_to_active_users_and_can_include_all_statuses(): void
    {
        User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Active Employee',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'employee_number' => '20250002',
            'name' => 'Inactive Employee',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('leave-credits.api.index'))
            ->assertOk()
            ->assertJsonFragment(['employee_name' => 'Active Employee'])
            ->assertJsonMissing(['employee_name' => 'Inactive Employee']);

        $this->actingAs($this->admin)
            ->getJson(route('leave-credits.api.index', ['status' => 'all']))
            ->assertOk()
            ->assertJsonFragment(['employee_name' => 'Active Employee'])
            ->assertJsonFragment(['employee_name' => 'Inactive Employee']);
    }

    public function test_leave_credit_api_can_update_single_and_bulk_balances(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        $this->actingAs($this->admin)
            ->patchJson(route('leave-credits.api.update', $employee), [
                'pto' => 12.5,
            ])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('user.pto', 12.5);

        $this->assertDatabaseHas('request_credits', [
            'user_id' => $employee->id,
            'pto' => 12.5,
            'wfh' => 4,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('leave-credits.api.bulk-update'), [
                'dry_run' => true,
                'credits' => [
                    [
                        'employee_number' => 20250001,
                        'pto' => 20,
                        'wfh' => 6,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('updated', 0)
            ->assertJsonPath('results.0.status', 'matched');

        $this->assertDatabaseHas('request_credits', [
            'user_id' => $employee->id,
            'pto' => 12.5,
            'wfh' => 4,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('leave-credits.api.bulk-update'), [
                'credits' => [
                    [
                        'employee_number' => 20250001,
                        'pto' => 20,
                        'wfh' => 6,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('results.0.status', 'updated');

        $this->assertDatabaseHas('request_credits', [
            'user_id' => $employee->id,
            'pto' => 20,
            'wfh' => 6,
        ]);
    }

    public function test_pnc_admin_can_update_leave_balance_from_report(): void
    {
        $this->admin->forceFill(['legacy_roles' => ['user', 'pnc.admin']])->save();

        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        $this->actingAs($this->admin)
            ->patch(route('leave-credits.balance.update', $employee), [
                'pto' => 12.5,
                'status' => 'all',
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ])
            ->assertRedirect(route('leave-credits.index', [
                'status' => 'all',
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]));

        $this->assertDatabaseHas('request_credits', [
            'user_id' => $employee->id,
            'pto' => 12.5,
        ]);
    }

    public function test_credit_carry_over_request_approval_adds_to_approved_carry_over_without_calendar(): void
    {
        Mail::fake();

        $manager = User::factory()->create([
            'rank' => 'manager',
            'email' => 'manager@example.com',
        ]);
        $employee = User::factory()->create([
            'supervisor_id' => $manager->id,
            'email' => 'employee@example.com',
        ]);
        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        $this->actingAs($employee)
            ->post(route('requests.store'), [
                'request_kind' => 'credit-carry-over',
                'carry_over_days' => 2.5,
                'reason' => 'Unused approved leave from prior cycle',
            ])
            ->assertRedirect(route('my-requests'));

        $staffRequest = StaffRequest::where('user_id', $employee->id)->firstOrFail();

        $this->assertSame(StaffRequest::TYPE_CREDIT_CARRY_OVER, $staffRequest->type);
        $this->assertSame('pending', $staffRequest->status);
        $this->assertEquals(2.5, (float) $staffRequest->number_of_days);

        $this->actingAs($manager)
            ->post(route('requests.process', $staffRequest), [
                'action_type' => 'approve',
                'remarks' => 'Approved carry over.',
            ])
            ->assertRedirect(route('requests.manage'));

        $staffRequest->refresh();
        $employee->refresh();

        $this->assertSame('approved', $staffRequest->status);
        $this->assertNull($staffRequest->google_event_id);
        $this->assertEquals(2.5, (float) $employee->requestCredit->approved_carry_over);
    }

    public function test_replenishment_adds_approved_carry_over_resets_it_and_records_run_history(): void
    {
        $this->admin->givePermissionTo('leave-credits.initialize');
        OrgSetting::query()->update([
            'pto_default' => 15,
            'wfh_default' => 5,
            'last_leave_replenished_on' => null,
        ]);

        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create([
            'user_id' => $employee->id,
            'pto' => 3,
            'wfh' => 1,
            'approved_carry_over' => 4.5,
        ]);

        $this->actingAs($this->admin)
            ->post(route('org-settings.initiate-leave'))
            ->assertRedirect();

        $this->assertDatabaseHas('request_credits', [
            'user_id' => $employee->id,
            'pto' => 19.5,
            'wfh' => 5,
            'approved_carry_over' => 0,
        ]);

        $this->assertSame(
            now()->toDateString(),
            OrgSetting::first()->last_leave_replenished_on->toDateString()
        );

        $this->assertSame(1, LeaveReplenishmentRun::count());

        $run = LeaveReplenishmentRun::first();
        $this->assertSame(now()->toDateString(), $run->run_date->toDateString());
        $this->assertEquals(15, (float) $run->pto_default);
        $this->assertEquals(5, (float) $run->wfh_default);
        $this->assertEquals(4.5, (float) $run->total_approved_carry_over);
        $this->assertSame($this->admin->id, $run->run_by);

        $this->assertSame(User::count(), LeaveReplenishmentRunItem::count());

        $item = LeaveReplenishmentRunItem::where('user_id', $employee->id)->firstOrFail();
        $this->assertSame($run->id, $item->leave_replenishment_run_id);
        $this->assertSame($employee->id, $item->user_id);
        $this->assertSame('20250001', $item->employee_number);
        $this->assertSame('Jane Employee', $item->employee_name);
        $this->assertSame('jane@example.com', $item->employee_email);
        $this->assertEquals(3, (float) $item->previous_pto);
        $this->assertEquals(1, (float) $item->previous_wfh);
        $this->assertEquals(15, (float) $item->pto_default);
        $this->assertEquals(5, (float) $item->wfh_default);
        $this->assertEquals(4.5, (float) $item->approved_carry_over_applied);
        $this->assertEquals(19.5, (float) $item->initialized_pto);
        $this->assertEquals(5, (float) $item->initialized_wfh);
    }

    public function test_sanctum_token_can_read_and_update_leave_credits(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);

        RequestCredit::create(['user_id' => $employee->id, 'pto' => 8, 'wfh' => 4]);

        $token = $this->admin->createToken('test-pnc-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/leave-credits?date_from=2026-06-01&date_to=2026-06-26')
            ->assertOk()
            ->assertJsonFragment([
                'employee_number' => '20250001',
                'employee_name' => 'Jane Employee',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/leave-credits/' . $employee->id, [
                'pto' => 15,
            ])
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('user.pto', 15);
    }

    public function test_leave_credit_csv_download_is_available(): void
    {
        $employee = User::factory()->create([
            'employee_number' => '20250001',
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
        ]);
        RequestCredit::create(['user_id' => $employee->id, 'pto' => 10, 'wfh' => 4]);

        $response = $this->actingAs($this->admin)
            ->get(route('leave-credits.csv', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-26',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Jane Employee', $csv);
        $this->assertStringContainsString('Employee number', $csv);
        $this->assertStringContainsString('Starting leave credits', $csv);
        $this->assertStringContainsString('Compensatory time-off total', $csv);
        $this->assertStringContainsString('20250001', $csv);
        $this->assertStringContainsString('10.00', $csv);
    }

    public function test_leave_credit_pdf_download_is_available(): void
    {
        $employee = User::factory()->create(['name' => 'Jane Employee', 'email' => 'jane@example.com']);
        RequestCredit::create(['user_id' => $employee->id, 'pto' => 10, 'wfh' => 4]);

        $response = $this->actingAs($this->admin)
            ->get(route('leave-credits.pdf', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-26',
            ]));

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
