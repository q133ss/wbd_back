<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private function getProductsCount($categories)
    {
        $categoryProductCounts = [];

        // Проходимся по всем родительским категориям
        foreach ($categories as $category) {
            $categoryProductCounts[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'product_count' => $this->countProductsInCategory($category)
            ];
        }
        return $categoryProductCounts;
    }
    public function index()
    {
        $categories = Category::where('parent_id', null)->get();
        $categoryProductCounts = $this->getProductsCount($categories);

        return response()->json($categoryProductCounts);
    }

    private function countProductsInCategory($category)
    {
        $count = $category->products()->count();

        foreach ($category->children as $childCategory) {
            $count += $this->countProductsInCategory($childCategory);
        }

        return $count;
    }

    public function indexSubCategory(string $id)
    {
        $categories = Category::findOrFail($id)->children;
        $categoryProductCounts = $this->getProductsCount($categories);
        return response()->json($categoryProductCounts);
    }
}
