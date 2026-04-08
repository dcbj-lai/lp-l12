<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class BackfillPreferredName extends Command
{
    protected $signature = 'users:backfill-preferred-name';

    protected $description = 'Populate preferred_name for users who do not have one';

    public function handle(): int
    {
        $this->info('Starting backfill of preferred_name...');

        $count = 0;

        User::whereNull('preferred_name')
            ->chunkById(100, function ($users) use (&$count) {

                foreach ($users as $user) {

                    if (!empty($user->name)) {
                        $user->preferred_name = explode(' ', trim($user->name))[0];
                        $user->save();
                        $count++;
                    }
                }

            });

        $this->info("Completed. Updated {$count} users.");

        return Command::SUCCESS;
    }
}
