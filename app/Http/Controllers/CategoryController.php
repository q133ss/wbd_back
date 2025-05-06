<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        return Cache::remember('categories_index', 600, function () {
            $categories = Category::with(['img', 'children.products'])->whereNull('parent_id')->get();

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
            $categories = Category::with(['img', 'children.products'])->findOrFail($id)->children;

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
