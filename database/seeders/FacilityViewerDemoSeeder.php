<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FacilityViewerDemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment('local'), 403);
        $this->call(ResourceRoleSeeder::class);
        $user = User::firstOrCreate(['email' => 'facilities.viewer@example.test'], [
            'name' => 'Facilities Viewer Demo', 'password' => 'ViewerLocal2026!', 'is_active' => true,
        ]);
        $user->syncRoles(['facility.viewer']);
    }
}
