<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AccessAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'user',
            'access.admin',
            'super.admin',
            'sys.admin',
            'pnc.staff',
            'pnc.admin',
            'finance.staff',
            'finance.admin',
            'frontdesk.staff',
            'acad.admin',
            'guidance.admin',
            'guidance.staff',
            'comms.admin',
            'clinic.admin',
            'facility.admin',
            'facility.approver',
            'facility.user',
        ] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
