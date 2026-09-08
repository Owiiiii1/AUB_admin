<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoursesGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CoreAcademySeeder::class);
    }
}
