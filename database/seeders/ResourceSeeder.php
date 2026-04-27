<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resource;
use App\Models\User;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'laicportal000@gmail.com')->first();

        if (!$admin) {
            throw new \Exception('Super Admin user not found. Run user seeder first.');
        }

        // 🏢 Conference Room
        Resource::updateOrCreate(
            ['name' => 'Conference Room'],
            [
                'type' => 'room',
                'description' => 'Main conference room for meetings',
                'location' => '2nd Floor',
                'capacity' => 20,
                'created_by' => $admin->id,
            ]
        );

        // 📺 Samsung Smart TV
        Resource::updateOrCreate(
            ['name' => 'Samsung Smart TV 58"'],
            [
                'type' => 'equipment',
                'description' => '58-inch smart TV for presentations',
                'created_by' => $admin->id,
            ]
        );

        // 🎤 Logitech Rally Plus
        Resource::updateOrCreate(
            ['name' => 'Logitech Rally Plus'],
            [
                'type' => 'equipment',
                'description' => 'Video conferencing system with mic and camera',
                'created_by' => $admin->id,
            ]
        );
    }
}
