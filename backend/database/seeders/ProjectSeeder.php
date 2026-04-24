<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Project::create([
            'name' => 'Annajah Core Platform',
            'slug' => 'annajah-core',
            'description' => 'The main bug tracking and management application.',
        ]);
    }
}
