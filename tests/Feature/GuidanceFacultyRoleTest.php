<?php

namespace Tests\Feature;

use App\Livewire\Guidance\CheckInConsultation;
use App\Models\Client;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuidanceFacultyRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guidance_teacher_list_uses_active_faculty_role_instead_of_department(): void
    {
        $facultyDepartment = Department::create(['name' => 'Faculty']);
        $academicsDepartment = Department::create(['name' => 'Academics']);
        Role::findOrCreate('faculty', 'web');

        $roleFaculty = User::factory()->create([
            'name' => 'Role Faculty Teacher',
            'email' => 'role.faculty.guidance@example.com',
            'department_id' => $academicsDepartment->id,
            'is_active' => true,
        ]);
        $roleFaculty->assignRole('faculty');

        User::factory()->create([
            'name' => 'Department Only Teacher',
            'email' => 'department.only.guidance@example.com',
            'department_id' => $facultyDepartment->id,
            'is_active' => true,
        ]);

        $inactiveFaculty = User::factory()->create([
            'name' => 'Inactive Role Teacher',
            'email' => 'inactive.faculty.guidance@example.com',
            'is_active' => false,
        ]);
        $inactiveFaculty->assignRole('faculty');

        $client = Client::create([
            'first_name' => 'Guidance',
            'last_name' => 'Student',
            'email' => 'guidance.student@example.com',
        ]);

        Livewire::test(CheckInConsultation::class, ['client' => $client])
            ->assertSee('Role Faculty Teacher')
            ->assertDontSee('Department Only Teacher')
            ->assertDontSee('Inactive Role Teacher');
    }
}