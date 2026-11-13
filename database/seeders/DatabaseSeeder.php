<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders to populate data
        $this->call([
            GlobalCategoriesSeeder::class, // Run first to create global categories
            DefaultDataSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
