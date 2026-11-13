<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class GlobalCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if global categories already exist
        if (Category::whereNull('user_id')->exists()) {
            $this->command->info('Global categories already exist. Skipping...');
            return;
        }

        // Create global default categories (same as in Flutter app)
        $defaultCategories = [
            // Income categories
            ['name' => 'حقوق', 'type' => 'income', 'color' => 0xFF10B981, 'icon' => 0],
            ['name' => 'فروش', 'type' => 'income', 'color' => 0xFF3B82F6, 'icon' => 0],
            ['name' => 'سود سرمایه‌گذاری', 'type' => 'income', 'color' => 0xFFF59E0B, 'icon' => 0],
            ['name' => 'هدیه', 'type' => 'income', 'color' => 0xFF8B5CF6, 'icon' => 0],
            ['name' => 'سایر درآمدها', 'type' => 'income', 'color' => 0xFF6B7280, 'icon' => 0],
            
            // Expense categories
            ['name' => 'خوراک', 'type' => 'expense', 'color' => 0xFFEF4444, 'icon' => 0],
            ['name' => 'حمل و نقل', 'type' => 'expense', 'color' => 0xFF3B82F6, 'icon' => 0],
            ['name' => 'مسکن', 'type' => 'expense', 'color' => 0xFF8B5CF6, 'icon' => 0],
            ['name' => 'پوشاک', 'type' => 'expense', 'color' => 0xFFEC4899, 'icon' => 0],
            ['name' => 'سرگرمی', 'type' => 'expense', 'color' => 0xFF7C3AED, 'icon' => 0],
            ['name' => 'سلامت', 'type' => 'expense', 'color' => 0xFF10B981, 'icon' => 0],
            ['name' => 'آموزش', 'type' => 'expense', 'color' => 0xFF6366F1, 'icon' => 0],
            ['name' => 'سایر هزینه‌ها', 'type' => 'expense', 'color' => 0xFF6B7280, 'icon' => 0],
        ];

        foreach ($defaultCategories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'type' => $categoryData['type'],
                'color' => $categoryData['color'],
                'icon' => $categoryData['icon'],
                'user_id' => null, // Global category
            ]);
        }

        $this->command->info('Global categories created successfully!');
    }
}

