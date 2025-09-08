<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    private function countProductsWithChildren(Category $category): int
    {
        $count = $category->products()->count();

        foreach ($category->children as $child) {
            $count += $this->countProductsWithChildren($child);
        }

        return $count;
    }

    public function index()
    {

        return Cache::remember('categories_index', 600, function () {
            $categories = Category::with(['img', 'children', 'children.products', 'products']) // рекурсивно подгружаем
            ->whereNull('parent_id')
                ->whereNotIn('id', ['1234', '1235', '1237', '131841', '131925'])
                ->get();

            // Фильтруем категории без товаров
            $categories = $categories->filter(function ($category) {
                return $this->countProductsWithChildren($category) > 0;
            });

            $adultCategory = Category::where('name', 'Товары для взрослых')->first();
            $adultCategoryIds = $this->getAllCategoryIds($adultCategory);

            $categoryProductCounts = $this->getProductsCount($categories);

            return collect($categoryProductCounts)->map(function ($category) use ($adultCategoryIds) {
                $category['requires_age_confirmation'] = in_array($category['category_id'], $adultCategoryIds);
                return $category;
            })->values();
        });
    }

    public function indexSubCategory(string $id)
    {
        $cacheKey = 'categories_sub_' . $id;

        $categoriesData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($id) {
            $categories = Category::with('children')->where('parent_id', $id)->get();

            return $categories->map(function ($category) {
                // 1. Собрать ID всех потомков
                $descendantIds = $category->getAllDescendantIds();
                $allCategoryIds = $descendantIds->push($category->id);

                // 2. Получить ID всех продуктов в этих категориях
                $productIds = \App\Models\Product::whereIn('category_id', $allCategoryIds)->pluck('id');

                // 3. Посчитать активные объявления
                $adCount = \App\Models\Ad::whereIn('product_id', $productIds)
                    ->where('status', true)
                    ->count();

                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'product_count' => $adCount,
                ];
            });
        });

        return $categoriesData;
    }

    private function getProductsCount($categories)
    {
        $categoryProductCounts = [];

        foreach ($categories as $category) {
            $categoryProductCounts[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'product_count' => $this->countProductsInCategory($category),
                'img' => $category->img,
            ];
        }

        return $categoryProductCounts;
    }

    private function countProductsInCategory($category)
    {
        $count = $category->products->count(); // используем загруженные продукты

        foreach ($category->children as $child) {
            $count += $this->countProductsInCategory($child);
        }

        return $count;
    }

    private function getAllCategoryIds(?Category $category)
    {
        if (!$category) return [];

        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds($child));
        }

        return $ids;
    }

    public function indexProducts(Request $request, string $id)
    {
        $page = request('page', 1); // учитываем пагинацию
        $filterHash = md5(serialize($request->all()));
        $cacheKey = "category_{$id}_ads_page_{$page}_filters_{$filterHash}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($id, $request) {
            // 1. Найти категорию
            $category = Category::with('children')->findOrFail($id);

            // 2. Собрать ID всех вложенных категорий (включая саму категорию)
            $descendantIds = $category->getAllDescendantIds();
            $allCategoryIds = $descendantIds->push($category->id);

            // 3. Найти продукты в этих категориях
            $productIds = Product::whereIn('category_id', $allCategoryIds)->pluck('id');

            // 4. Получить активные объявления
            return Ad::whereIn('product_id', $productIds)
                ->where('status', true)
                ->with('product')
                ->withFilter($request)
                ->paginate(18);
        });
    }
}
