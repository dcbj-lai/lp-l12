<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::all(); // Get all users

        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                $date = Carbon::now()->subDays($i)->toDateString();

                // Skip if record already exists
                if (Attendance::where('user_id', $user->id)->where('date', $date)->exists()) {
                    continue;
                }

                $checkInTime = Carbon::parse($date . ' ' . fake()->time('H:i:s', '08:00:00'));
                $checkOutTime = Carbon::parse($date . ' ' . fake()->time('H:i:s', '17:00:00'));

                // Set remarks to 'User' for consistency
                $remark = 'User';

                // Determine status based on check-in
                $checkIn = fake()->boolean(80) ? $checkInTime : null; // 80% chance of showing up
                $status = $checkIn ? 'Present' : 'Absent';
                $checkOut = $status === 'Absent' ? null : $checkOutTime;

                $hours_worked = Carbon::parse($checkIn)
            ->diffInMinutes($checkOut) / 60;

                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $status,
                    'remarks' => $remark,
                    'hours_worked' => $hours_worked,
                ]);
            }
        }
    }
}

