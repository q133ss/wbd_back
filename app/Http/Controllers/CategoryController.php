<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    private function getProductsCount($categories)
    {
        $categoryProductCounts = [];

        // Проходимся по всем родительским категориям
        foreach ($categories as $category) {
            $categoryProductCounts[] = [
                'category_id'   => $category->id,
                'category_name' => $category->name,
                'product_count' => $this->countProductsInCategory($category),
                'img'           => $category->img,
            ];
        }

        return $categoryProductCounts;
    }

    public function index()
    {
        $categories = Category::with('img', 'children')->whereNull('parent_id')->get();

        // Получаем ID категории "18+"
        $adultCategory = Category::where('name', 'Товары для взрослых')->first();

        // Собираем все ID, которые относятся к 18+
        $adultCategoryIds = $this->getAllCategoryIds($adultCategory);

        // Получаем категории с учётом количества товаров
        $categoryProductCounts = $this->getProductsCount($categories);

        // Оборачиваем в коллекцию и добавляем флаг age confirmation
        $categoryProductCounts = collect($categoryProductCounts)->map(function ($category) use ($adultCategoryIds) {
            $category['requires_age_confirmation'] = in_array($category['category_id'], $adultCategoryIds);
            return $category;
        })->values(); // ->values() сбрасывает ключи, если нужно индексированный массив

        return response()->json($categoryProductCounts);
    }

    private function getAllCategoryIds($category)
    {
        if (!$category) return [];

        $ids = collect([$category->id]);

        foreach ($category->children as $child) {
            $ids = $ids->merge($this->getAllCategoryIds($child));
        }

        return $ids->toArray();
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
        $categories = Category::with('img', 'children')->findOrFail($id)->children;

        // Получаем ID категории "18+"
        $adultCategory = Category::where('name', 'Товары для взрослых')->first();

        // Собираем все ID, которые относятся к 18+ и её подкатегориям
        $adultCategoryIds = $this->getAllCategoryIds($adultCategory);

        // Считаем товары
        $categoryProductCounts = $this->getProductsCount($categories);

        // Добавляем requires_age_confirmation
        $categoryProductCounts = collect($categoryProductCounts)->map(function ($category) use ($adultCategoryIds) {
            $category['requires_age_confirmation'] = in_array($category['category_id'], $adultCategoryIds);
            return $category;
        })->values();

        return response()->json($categoryProductCounts);
    }
}
