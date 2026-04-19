<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quranThemes = [
            'Belief in Allah',
            'Stories of Prophets',
            'Guidance for Daily Life',
            'Hereafter',
        ];

        foreach ($quranThemes as $theme) {
            \App\Models\Theme::updateOrCreate(
                ['name' => $theme, 'type' => 'PARA'],
                ['is_active' => true]
            );
        }

        $seerahThemes = [
            'Prophet Muhammad\'s Early Life',
            'The Revelation',
            'Persecution in Makkah',
            'The Hijrah',
            'Life in Madinah',
        ];

        foreach ($seerahThemes as $theme) {
            \App\Models\Theme::updateOrCreate(
                ['name' => $theme, 'type' => 'SEERAH'],
                ['is_active' => true]
            );
        }
    }
}
