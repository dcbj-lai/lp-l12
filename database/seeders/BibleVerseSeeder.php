<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BibleVerse;

class BibleVerseSeeder extends Seeder
{
    public function run()
    {
        BibleVerse::create([
            'text' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.',
            'reference' => 'John 3:16',
        ]);

        BibleVerse::create([
            'text' => 'I can do all things through Christ who strengthens me.',
            'reference' => 'Philippians 4:13',
        ]);
    }
}
