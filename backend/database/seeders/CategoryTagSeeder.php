<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Tag;

class CategoryTagSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Frontend', 'Backend', 'Database', 'Infrastructure', 'UI/UX', 'Mobile'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat]);
        }

        $tags = [
            ['name' => 'API', 'color' => '#3B82F6'], // blue
            ['name' => 'Authentication', 'color' => '#8B5CF6'], // purple
            ['name' => 'Performance', 'color' => '#F59E0B'], // amber
            ['name' => 'Security', 'color' => '#EF4444'], // red
            ['name' => 'Enhancement', 'color' => '#10B981'], // green
            ['name' => 'Documentation', 'color' => '#6B7280'], // gray
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag['name']], ['color' => $tag['color']]);
        }
    }
}
