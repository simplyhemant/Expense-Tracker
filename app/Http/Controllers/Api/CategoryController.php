<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();
        
        // Filter by type if provided as query string or route parameter
        $typeFilter = $request->route('type') ?? $request->query('type');
        if (! empty($typeFilter)) {
            $query->where('type', $typeFilter);
        }
        
        // Get categories for authenticated user OR global categories
        $query->where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->orWhereNull('user_id'); // Include global categories
        });
        
        $categories = $query->get();
        
        \Log::debug('Fetching categories', [
            'user_id' => $request->user()->id,
            'type_filter' => $typeFilter,
            'count' => $categories->count(),
            'categories' => $categories->map(fn($c) => [
                'id' => $c->id, 
                'name' => $c->name, 
                'type' => $c->type
            ]),
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'color' => 'required|integer',
            'icon' => 'required|integer',
        ]);

        $validated['name'] = trim($validated['name'] ?? '');

        if ($validated['name'] === '') {
            return response()->json([
                'success' => false,
                'message' => 'نام دسته‌بندی نمی‌تواند خالی باشد'
            ], 422);
        }

        $normalizedName = Str::lower($validated['name']);

        $isDuplicate = Category::where('user_id', $request->user()->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->exists();

        if ($isDuplicate) {
            return response()->json([
                'success' => false,
                'message' => 'این نام دسته‌بندی قبلاً استفاده شده است'
            ], 422);
        }

        \Log::debug('Creating category', [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'user_id' => $request->user()->id,
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'color' => $validated['color'],
            'icon' => $validated['icon'],
            'user_id' => $request->user()->id,
        ]);

        \Log::debug('Category created', [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت اضافه شد',
            'data' => $category
        ], 201);
    }

    public function show(Request $request, Category $category)
    {
        // Check if category belongs to authenticated user or is global
        if ($category->user_id !== $request->user()->id && $category->user_id !== null) {
            return response()->json(['message' => 'دسته‌بندی یافت نشد'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function update(Request $request, Category $category)
    {
        // Only allow updating user's own categories, not global categories
        if ($category->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید دسته‌بندی پیش‌فرض یا سایر کاربران را تغییر دهید'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:income,expense',
            'color' => 'sometimes|integer',
            'icon' => 'sometimes|integer',
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['name'] = trim($validated['name'] ?? '');

            if ($validated['name'] === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'نام دسته‌بندی نمی‌تواند خالی باشد'
                ], 422);
            }

            $normalizedName = Str::lower($validated['name']);

            $isDuplicate = Category::where('user_id', $request->user()->id)
                ->where('id', '!=', $category->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                ->exists();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'این نام دسته‌بندی قبلاً استفاده شده است'
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت ویرایش شد',
            'data' => $category
        ]);
    }

    public function destroy(Request $request, Category $category)
    {
        // Only allow deleting user's own categories, not global categories
        if ($category->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید دسته‌بندی پیش‌فرض یا سایر کاربران را حذف کنید'
            ], 403);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت حذف شد'
        ]);
    }

    public function seedDefaultCategories(Request $request)
    {
        $userId = $request->user()->id;
        
        // Check if user already has categories
        $existingCategories = Category::where('user_id', $userId)->count();
        if ($existingCategories > 0) {
            return response()->json([
                'success' => false,
                'message' => 'User already has categories'
            ], 400);
        }

        // Default categories data
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

        $createdCategories = [];
        foreach ($defaultCategories as $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'type' => $categoryData['type'],
                'color' => $categoryData['color'],
                'icon' => $categoryData['icon'],
                'user_id' => $userId,
            ]);
            $createdCategories[] = $category;
        }

        return response()->json([
            'success' => true,
            'message' => 'Default categories seeded successfully',
            'data' => $createdCategories
        ]);
    }
}
