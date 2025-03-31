<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = ['PNC', 'Finance', 'Digital Transformation', 'Academics', 'Campus Life', 'Operations'];

        foreach ($departments as $department) {
            Department::firstOrCreate(['name' => $department]);
        }
    }
}

