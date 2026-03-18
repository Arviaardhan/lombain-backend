<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SkillCategory;

class SkillCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SkillCategory::insert([
            ['name' => 'IT'],
            ['name' => 'Business'],
            ['name' => 'Design'],
            ['name' => 'Marketing'],
        ]);
    }
}
