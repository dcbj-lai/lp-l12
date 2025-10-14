<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddDepartment extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'department:add {name : The name of the department}';

    /**
     * The console command description.
     */
    protected $description = 'Add a new department record to the departments table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        // Check if it already exists
        if (DB::table('departments')->where('name', $name)->exists()) {
            $this->error("Department '{$name}' already exists.");
            return self::FAILURE;
        }

        DB::table('departments')->insert([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✅ Department '{$name}' added successfully.");

        return self::SUCCESS;
    }
}
