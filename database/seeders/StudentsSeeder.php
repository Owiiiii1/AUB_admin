<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StudentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CoreAcademySeeder::class);
    }
}
