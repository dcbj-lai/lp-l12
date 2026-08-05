<?php

namespace Tests\Feature;

use App\Livewire\Clinic\CheckInConsultation as ClinicCheckInConsultation;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HealthWellnessFacultyRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_role_migration_backfills_department_users_without_replacing_roles(): void
    {
        $migration = require database_path('migrations/2026_08_05_000000_create_faculty_role_and_backfill_department_users.php');
        $migration->down();

        $facultyDepartment = Department::create(['name' => 'Faculty']);
        Role::findOrCreate('user', 'web');

        $facultyUser = User::factory()->create([
            'department_id' => $facultyDepartment->id,
        ]);
        $facultyUser->assignRole('user');

        $migration->up();

        $facultyUser->refresh();

        $this->assertSame($facultyDepartment->id, $facultyUser->department_id);
        $this->assertTrue($facultyUser->hasAllRoles(['user', 'faculty']));
    }

    public function test_active_faculty_scope_uses_role_instead_of_department(): void
    {
        $facultyDepartment = Department::create(['name' => 'Faculty']);
        $academicsDepartment = Department::create(['name' => 'Academics']);
        Role::findOrCreate('faculty', 'web');

        $activeFaculty = User::factory()->create([
            'department_id' => $academicsDepartment->id,
            'is_active' => true,
        ]);
        $activeFaculty->assignRole('faculty');

        $inactiveFaculty = User::factory()->create([
            'department_id' => $facultyDepartment->id,
            'is_active' => false,
        ]);
        $inactiveFaculty->assignRole('faculty');

        User::factory()->create([
            'department_id' => $facultyDepartment->id,
            'is_active' => true,
        ]);

        $this->assertSame(
            [$activeFaculty->id],
            User::activeFaculty()->pluck('id')->all()
        );
    }

    public function test_clinic_check_in_list_uses_active_faculty_role(): void
    {
        $facultyDepartment = Department::create(['name' => 'Faculty']);
        $academicsDepartment = Department::create(['name' => 'Academics']);
        Role::findOrCreate('faculty', 'web');

        $roleFaculty = User::factory()->create([
            'name' => 'Role Faculty Teacher',
            'email' => 'role.faculty@example.com',
            'department_id' => $academicsDepartment->id,
            'is_active' => true,
        ]);
        $roleFaculty->assignRole('faculty');

        User::factory()->create([
            'name' => 'Department Only Teacher',
            'email' => 'department.only@example.com',
            'department_id' => $facultyDepartment->id,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'first_name' => 'Clinic',
            'last_name' => 'Student',
            'type' => 'student',
        ]);

        Livewire::test(ClinicCheckInConsultation::class, ['patient' => $patient])
            ->assertSee('Role Faculty Teacher')
            ->assertDontSee('Department Only Teacher');

    }

    public function test_user_settings_show_faculty_as_both_a_department_and_role(): void
    {
        Permission::findOrCreate('users.edit', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('users.edit');

        $facultyDepartment = Department::create(['name' => 'Faculty']);
        $user = User::factory()->create([
            'department_id' => $facultyDepartment->id,
        ]);

        $this->actingAs($admin)
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee('value="faculty"', false)
            ->assertSee('value="'.$facultyDepartment->id.'"', false);
    }
}
