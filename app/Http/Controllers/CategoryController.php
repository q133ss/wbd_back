<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Category;
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

        return Cache::remember($cacheKey, 600, function () use ($id) {
            $parentCategory = Category::with([
                'children.children.children', // загружаем детей вглубь
                'children.products',
                'children.img',
                'children' => function ($query) {
                    $query->with('products');
                },
            ])->findOrFail($id);

            $categories = $parentCategory->children;

            // Фильтрация: убираем категории без товаров и без товаров у подкатегорий
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

    public function indexProducts(string $id)
    {
        $category = Category::findOrFail($id);
        $ids = $this->getAllCategoryIds($category);

        return Ad::leftJoin('products', 'ads.product_id', '=', 'products.id')
            ->whereIn('products.category_id', $ids)
            ->where('ads.status', true)
            ->where('ads.is_archived', false)
            ->select('ads.*')
            ->orderBy('created_at', 'desc')
            ->paginate(18);
    }
}
