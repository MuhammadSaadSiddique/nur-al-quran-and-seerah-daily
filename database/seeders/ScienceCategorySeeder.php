<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScienceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Neuroscience / Psychology',
                'slug' => 'neuroscience_psychology',
                'emoji' => '🧠',
                'mapped_fields' => 'neuroscience,psychology',
            ],
            [
                'name' => 'Astronomy / Cosmology',
                'slug' => 'astronomy_cosmology',
                'emoji' => '🪐',
                'mapped_fields' => 'astronomy,cosmology',
            ],
            [
                'name' => 'Geology',
                'slug' => 'geology',
                'emoji' => '🪨',
                'mapped_fields' => 'geology',
            ],
            [
                'name' => 'Biology',
                'slug' => 'biology',
                'emoji' => '🧬',
                'mapped_fields' => 'biology',
            ],
            [
                'name' => 'Embryology',
                'slug' => 'embryology',
                'emoji' => '🍼',
                'mapped_fields' => 'embryology',
            ],
            [
                'name' => 'Oceanography',
                'slug' => 'oceanography',
                'emoji' => '🌊',
                'mapped_fields' => 'oceanography',
            ],
            [
                'name' => 'Hydrology',
                'slug' => 'hydrology',
                'emoji' => '💧',
                'mapped_fields' => 'hydrology',
            ],
            [
                'name' => 'Meteorology',
                'slug' => 'meteorology',
                'emoji' => '🌀',
                'mapped_fields' => 'meteorology',
            ],
            [
                'name' => 'Physics',
                'slug' => 'physics',
                'emoji' => '⚡',
                'mapped_fields' => 'physics',
            ],
            [
                'name' => 'General Science',
                'slug' => 'general',
                'emoji' => '🔬',
                'mapped_fields' => 'general',
            ],
        ];

        foreach ($categories as $cat) {
            DB::table('science_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'emoji' => $cat['emoji'],
                    'mapped_fields' => $cat['mapped_fields'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
