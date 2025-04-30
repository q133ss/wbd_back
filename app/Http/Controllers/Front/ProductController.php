<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use App\Services\WBService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function getAllCategoryIds(?Category $category): array
    {
        if (!$category) return [];

        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds($child));
        }

        return $ids;
    }

    public function index(Request $request)
    {
        $adultCategoryIds = cache()->rememberForever('adult_category_ids', function () {
            $adultRoot = Category::with('children')->where('name', 'Товары для взрослых')->first();
            return $adultRoot ? $this->getAllCategoryIds($adultRoot) : [];
        });
        $adsQuery = Ad::with(['product'])
        ->whereHas('product', function ($query) use ($adultCategoryIds) {
            $query->whereNotIn('category_id', $adultCategoryIds);
        })
            ->withFilter($request)
            ->where('ads.status', true)
            ->withSorting($request);

        $ads = $adsQuery->paginate(18);

        return response()->json($ads);
    }


    public function show(string $id)
    {
        $ad = Ad::findOrFail($id);
        $ad->increment('views_count');
        return $ad;
    }

    public function related(string $id)
    {
        $ad = Ad::findOrFail($id);
        if ($ad->product?->category_id == null) {
            $related = Ad::where('id', '!=', $id)->orderBy('created_at', 'desc')->take(6)->get();
        } else {
            $related = Ad::whereHas('product', function ($query) use ($ad) {
                $query->where('category_id', $ad->product?->category_id);
            })
                ->where('id', '!=', $id)
                ->inRandomOrder()
                ->take(6)
                ->get();
        }

        return response()->json($related);
    }

    public function showFeedbacks(string $productId, int $page)
    {
        $service = new WBService();
        return $service->getReviews($productId, $page);
    }
}
