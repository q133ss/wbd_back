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

    /**
     * Собираем все id из nodes (включая вложенные).
     */
    private function collectAllNodes($category, $allCategories)
    {
        $result = collect($category->nodes ?? []);

        foreach ($category->nodes ?? [] as $childId) {
            $child = $allCategories->get($childId);
            if ($child) {
                $result = $result->merge($this->collectAllNodes($child, $allCategories));
            }
        }

        return $result;
    }

    public function index()
    {
        # TODO доделать отображение и выложить!
        $categoryIds = [
            6994,
              1,
              2192,
              16107,
              115,
              17006,
              258,
              306,
              10326,
              6119,
              481,
              5486,
              519,
              543,
              131111,
              8421,
              566,
              629,
              10296,
              4863,
              784,
              62057,
              131286,
              4830,
              1023,
              131840,
              62813,
              131450,
              130624,
        ];

        return Category::with(['img'])->whereIn('id', $categoryIds)->get()->map(function ($category) {
            $allCategories = Category::all()->keyBy('id');
            $nodes = $this->collectAllNodes($category, $allCategories)->unique()->values();
            $allProducts = Product::whereIn('category_id', $nodes)->pluck('id')->all();

            # TODO не отображает товары
            $adCount = Ad::whereIn('product_id', $allProducts)
                ->where('status', true)
                ->count();

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'product_count' => $adCount,
                'img' => $category->img,
            ];
        });
    }


    public function indexSubCategory(string $id)
    {
        $cacheKey = 'sub_categories_' . $id;
        return Cache::remember($cacheKey, now()->addYear(), function () use ($id) {
            $categories = Category::where('parent_id', $id)->get();
            $allCategories = Category::all()->keyBy('id');

            return $categories->map(function ($category) use ($allCategories) {
                $nodes = $this->collectAllNodes($category, $allCategories)->unique()->values();

                $allProducts = Product::whereIn('category_id', $nodes)->pluck('id')->all();

                # TODO не отображает товары
                $adCount = Ad::whereIn('product_id', $allProducts)
                    ->where('status', true)
                    ->count();

                return [
                    'category_id'   => $category->id,
                    'category_name' => $category->name,
                    'product_count' => $adCount,
                ];
            });
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
