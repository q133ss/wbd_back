<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdStat;
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
            ->whereDoesntHave('product', function ($query) use ($adultCategoryIds) {
                $query->whereIn('products.category_id', $adultCategoryIds);
            })
            ->withFilter($request)
            ->where('ads.status', true)
            ->withSorting($request);

        $ads = $adsQuery->paginate(18);

        // 👇 Массовое логирование просмотров
        $user = auth()->user();
        $ip = $user ? null : $request->ip();
        $now = now();

        $bulk = [];

        foreach ($ads as $ad) {
            // Проверяем, был ли уже просмотр этого объявления этим юзером/IP
            $alreadyLogged = AdStat::where('ad_id', $ad->id)
                ->where('type', 'view')
                ->where(function ($q) use ($user, $ip) {
                    if ($user) {
                        $q->where('user_id', $user->id);
                    } else {
                        $q->where('ip_address', $ip);
                    }
                })
                ->exists();

            if (!$alreadyLogged) {
                $bulk[] = [
                    'ad_id'      => $ad->id,
                    'user_id'    => $user?->id,
                    'ip_address' => $user ? null : $ip,
                    'type'       => 'view',
                    'created_at' => $now,
                ];
            }
        }

        // Массовая вставка
        if (!empty($bulk)) {
            AdStat::insert($bulk);
        }

        return response()->json($ads);
    }


    public function show(string $id)
    {
        $ad = Ad::findOrFail($id);
        $ad->increment('views_count');
        $ad->logStat('click');
        return $ad;
    }

    public function related(string $id)
    {
        $ad = Ad::with('product')->findOrFail($id);
        $query = Ad::where('id', '!=', $id)->with('product');

        // Если у товара есть категория, ищем в той же категории
        if ($ad->product?->category_id) {
            $query->whereHas('product', function ($q) use ($ad) {
                $q->where('category_id', $ad->product->category_id);
            });
        }

        // Получаем 8 случайных товаров
        $related = $query->inRandomOrder()->take(8)->get();

        // Если товаров меньше 8, добираем последними добавленными
        if ($related->count() < 8) {
            $remaining = 8 - $related->count();
            $additionalAds = Ad::where('id', '!=', $id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->take($remaining)
                ->get();

            $related = $related->merge($additionalAds);
        }

        return response()->json($related);
    }

    public function showFeedbacks(string $productId, int $page)
    {
        $service = new WBService();
        return $service->getReviews($productId, $page);
    }
}
