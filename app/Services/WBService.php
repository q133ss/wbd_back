<?php

namespace App\Services;

use App\Exceptions\JsonException;
use App\Jobs\LoadProductVariationsJob;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WBService extends BaseService
{
    /**
     * Проверяет товар на уникальность!
     *
     * @throws JsonException
     */
    public function productCheck(string $id): bool
    {
        if (Product::where('wb_id', $id)->exists()) {
            return false;
        }

        return true;
    }

    public function getSupplier($supplier_id)
    {
        try {
            $getSupplierUrl   = "https://static-basket-01.wbbasket.ru/vol0/data/supplier-by-id/{$supplier_id}.json";
            $supplierResponse = Http::get($getSupplierUrl);

            return $supplierResponse->json();
        } catch (\Exception $e) {
            \Log::error('Ошибка при получении юо лица. '.$e->getMessage);
            $response = $this->formatResponse('false', 'Ошибка при получении данных о магазине', 500);

            return $this->sendResponse($response);
        }
    }

    /**
     * @param $shop
     * @return bool
     */
    private function checkShop($shop): bool
    {
        $user = auth('sanctum')->user();

        // Если магазина нет, создаем новый
        if ($user->shop == null) {
            Shop::create([
                'user_id'     => $user->id,
                'supplier_id' => $shop['supplier_id'],
                'inn'         => $shop['inn'],
                'legal_name'  => $shop['legal_name'],
                'wb_name'     => $shop['wb_name'],
            ]);
            return true;
        } else {
            // Проверяем магазин
            if ((string)$shop['supplier_id'] != (string)$user->shop?->supplier_id) {
                return false;
            }
            return true;
        }
    }

    // Получает все вариации товара по артикулу
    public function getAllVariations(string $wbId)
    {
        $pathData = $this->generatePathData($wbId);
        try {
            $cardUrl = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$wbId}/info/ru/card.json";
            $cardResponse = Http::get($cardUrl);

            if (!$cardResponse->successful()) {
                throw new \Exception("Failed to fetch card data");
            }

            $variations = $cardResponse->json()['colors'] ?? [];
            if( empty($variations)) {
                throw new \Exception("Вариации не найдены");
            }

            return $variations;

        } catch (\Exception $e) {
            \Log::error("WB Reviews Error: " . $e->getMessage());
            return [
                'reviews' => [],
                'summary' => null,
                'pagination' => null
            ];
        }
    }

    // передаем артикул и получаем отзывы
    public function getReviews(string $adsId, int $page = 1, int $perPage = 10)
    {
        $wbId = Ad::with('product:id,wb_id')->find($adsId)?->product?->wb_id;

        if (!$wbId) {
            return response()->json(['error' => 'Ad not found'], 404);
        }

        $pathData = $this->generatePathData($wbId);
        $cacheKey = "wb_reviews_{$wbId}";

        if (Cache::has($cacheKey)) {
            $reviewsData = Cache::get($cacheKey);
        } else {
            try {
                $cardUrl = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$wbId}/info/ru/card.json";
                $cardResponse = Http::get($cardUrl);

                if (!$cardResponse->successful()) {
                    throw new \Exception("Failed to fetch card data");
                }

                $imtId = $cardResponse->json()['imt_id'] ?? null;
                if (!$imtId) throw new \Exception("imt_id not found");

                $reviewsUrl = "https://feedbacks2.wb.ru/feedbacks/v2/{$imtId}";
                $reviewsUrlFallback = "https://feedbacks1.wb.ru/feedbacks/v2/{$imtId}";

                $reviewsResponse = Http::get($reviewsUrl);

                if (!$reviewsResponse->successful()) {
                    throw new \Exception("Failed to fetch reviews");
                }

                $reviewsData = $reviewsResponse->json();

                if(empty($reviewsData['feedbacks'])) {
                    $reviewsResponseFallback = Http::get($reviewsUrlFallback);
                    if (!$reviewsResponseFallback->successful()) {
                        throw new \Exception("Failed to fetch reviews");
                    }

                    $reviewsFallbackData = $reviewsResponseFallback->json();
                    Cache::put($cacheKey, $reviewsFallbackData, now()->addDay());

                    return $this->formatReviewsResponse($reviewsFallbackData, $page, $perPage);
                }else{
                    Cache::put($cacheKey, $reviewsData, now()->addDay());
                }

            } catch (\Exception $e) {
                \Log::error("WB Reviews Error: " . $e->getMessage());
                return [
                    'reviews' => [],
                    'summary' => null,
                    'pagination' => null
                ];
            }
        }

        // Формируем структурированный ответ
        return $this->formatReviewsResponse($reviewsData, $page, $perPage);
    }

    private function formatReviewsResponse(array $reviewsData, int $page, int $perPage)
    {
        $allFeedbacks = $reviewsData['feedbacks'] ?? [];
        $total = count($allFeedbacks);

        // Пагинация
        $paginated = array_slice($allFeedbacks, ($page - 1) * $perPage, $perPage);

        // Форматируем каждый отзыв
        $formattedReviews = array_map(function($feedback) {
            return [
                'id' => $feedback['id'],
                'text' => $feedback['text'],
                'rating' => $feedback['productValuation'],
                'pros' => $feedback['pros'] ?? null,
                'cons' => $feedback['cons'] ?? null,
                'createdDate' => $feedback['createdDate'],
                'user' => $feedback['wbUserDetails']['name'] ?? 'Аноним',
                'answer' => $feedback['answer'],
                'photos' => $this->getReviewPhotos($feedback),
                'video' => $feedback['video'] ?? null
            ];
        }, $paginated);

        return [
            'reviews' => $formattedReviews,
            'summary' => [
                'averageRating' => $reviewsData['valuation'] ?? null,
                'totalReviews' => $reviewsData['feedbackCount'] ?? 0,
                'ratingDistribution' => $reviewsData['valuationDistributionPercent'] ?? null,
                'withPhotos' => $reviewsData['feedbackCountWithPhoto'] ?? 0,
                'withText' => $reviewsData['feedbackCountWithText'] ?? 0
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }

    private function getReviewPhotos(array $feedback): array
    {
        // Здесь можно добавить логику получения фото отзыва, если они есть
        return []; // Заглушка
    }

    /**
     * Генерирует URL-адреса изображений для товара Wildberries.
     *
     * @param  array  $product  Массив с ключами 'wb_id' (артикул товара) и 'pics' (количество изображений).
     * @return array Массив URL-адресов изображений.
     */
    private function generateImageUrls(array $product): array
    {
        $images = [];
        $wbId = (int) $product['wb_id'];
        $picsCount = $product['pics'] ?? 0;

        if ($picsCount <= 0) {
            return [];
        }

        $pathData = $this->generatePathData($wbId);
        $validImages = [];

        // Ограничим максимальное количество проверяемых изображений для производительности
        $maxImagesToCheck = min($picsCount, 20); // Проверяем не более 20 изображений

        // Используем пул запросов для параллельной проверки
        $responses = Http::pool(function (Pool $pool) use ($pathData, $wbId, $maxImagesToCheck) {
            $requests = [];
            for ($i = 1; $i <= $maxImagesToCheck; $i++) {
                $url = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$wbId}/images/big/{$i}.webp";
                $requests[] = $pool->head($url); // Используем HEAD для проверки без загрузки содержимого
            }
            return $requests;
        });

        // Собираем только валидные изображения
        for ($i = 1; $i <= $maxImagesToCheck; $i++) {
            //if (isset($responses[$i - 1]) && $responses[$i - 1]->successful()) {
                $validImages[] = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$wbId}/images/big/{$i}.webp";
            //}
        }

        return $validImages;
    }

    private function generatePathData(int $wbId): array
    {
        $vol = (int)floor($wbId / 100000);
        $part = (int)floor($wbId / 1000);

        $host = $this->determineHost($vol);

        return [
            'vol' => $vol,
            'part' => $part,
            'host' => $host
        ];
    }

    private function determineHost(int $vol): string
    {
        static $ranges = [
            [0, 143, '01'],
            [144, 287, '02'],
            [288, 431, '03'],
            [432, 719, '04'],
            [720, 1007, '05'],
            [1008, 1061, '06'],
            [1062, 1115, '07'],
            [1116, 1169, '08'],
            [1170, 1313, '09'],
            [1314, 1601, '10'],
            [1602, 1655, '11'],
            [1656, 1919, '12'],
            [1920, 2045, '13'],
            [2046, 2189, '14'],
            [2190, 2405, '15'],
            [2406, 2621, '16'],
            [2622, 2837, '17'],
            [2838, 3014, '18'],
            [3015, 3279, '19'],
            [3280, 3443, '20'],
            [3444, 3623, '21'],
            [3624, 3984, '22'],
            [3985, 4199, '23'],
            [4200, 4391, '24'],
            [4392, 4589, '25'],
            [4590, PHP_INT_MAX, '26'],
        ];

        foreach ($ranges as [$min, $max, $host]) {
            if ($vol >= $min && $vol <= $max) {
                return $host;
            }
        }

        return '26'; // Фолбек для неучтенных случаев
    }
    private function fetchDescription(string $wbId): ?string
    {
        $cacheKey = "wb_product_description_{$wbId}";

        return Cache::remember($cacheKey, now()->addDay(), function() use ($wbId) {
            $pathData = $this->generatePathData($wbId);

            try {
                $cardUrl = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$wbId}/info/ru/card.json";
                $cardResponse = Http::retry(3, 100)->get($cardUrl);

                if (!$cardResponse->successful()) {
                    return null;
                }

                return $cardResponse->json()['description'] ?? null;

            } catch (\Exception $exception) {
                Log::error("Failed to fetch description for product {$wbId}", [
                    'error' => $exception->getMessage(),
                    'url' => $cardUrl ?? null
                ]);
                return null;
            }
        });
    }

    /**
     * Ищет категорию и возвращает ее
     */
    private function makeCategory(array $product): mixed
    {
        $id = $product['id'] ?? null;

        $pathData = $this->generatePathData($id);
        $cardUrl = "https://basket-{$pathData['host']}.wbbasket.ru/vol{$pathData['vol']}/part{$pathData['part']}/{$id}/info/ru/card.json";
        \Log::info('CardURL: '.$cardUrl);
        $cardResponse = Http::get($cardUrl);

        if (!$cardResponse->successful()) {
            throw new \Exception("Failed to fetch card data");
        }

        $subcategoryName = $cardResponse->json()['subj_name'] ?? null; // Дочерняя категория
        $categoryName = $cardResponse->json()['subj_root_name'] ?? null; // Родительская категория

        if (!empty($subcategoryName)) {
            $finalCategory = $subcategoryName;
        } elseif (!empty($categoryName)) {
            $finalCategory = $categoryName;
        } else {
            $finalCategory = 'Без категории';
        }

        return Category::where('name', $finalCategory)
            ->pluck('id')
            ->first();
    }

    /**
     * Принимает в себя товар полученный от АПИ WB
     * Далее форматирует его для нашей БД
     */
    private function formatProductData(array $product): array
    {
        $product['wb_id'] = $product['id'];

        $price = 0;

        // Если нет salePriceU, пробуем взять цену из sizes[0].price.product
        if (!isset($product['salePriceU'])) {
            if (!empty($product['sizes']) && isset($product['sizes'][0]['price']['product'])) {
                $price = $product['sizes'][0]['price']['product'] / 100; // Делим на 100 (цена в копейках)
            }
        }else{
            $price = $product['salePriceU'] / 100;
        }

        // Если цена так и осталась 0, значит товара нет в наличии
        if ($price === 0) {
            return [];
        }

        $sizes = $product['sizes'] ?? [];
        if (!empty($sizes) && empty($sizes[0]['name'])) {
            $sizes = [];
        }

        return [
            'wb_id'              => $product['id'],
            'name'               => $product['name'],
            'price'              => $price,
            'brand'              => $product['brand']      ?? null,
            'discount'           => $product['sale']       ?? 0,
            'rating'             => $product['reviewRating'] ?? 0,
            'quantity_available' => $product['volume']     ?? 0,
            'supplier_id'        => $product['supplierId'] ?? null,
            'images'             => $this->generateImageUrls($product),
            'description'        => $this->fetchDescription($product['wb_id']),
            'supplier_rating'    => $product['supplierRating'] ?? 0,
            'category_id'        => $this->makeCategory($product),
            'colors'             => $product['colors'] ?? [],
            'sizes'              => $sizes ?? [],
        ];
    }

    /**
     * Возвращает объект товара из АПИ ВБ
     *
     * @return array|JsonResponse|mixed|void
     */
    private function loadProductData(string $product_id)
    {
        $cacheKey = "wb_product_{$product_id}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $url = "https://card.wb.ru/cards/v2/detail?appType=1&curr=rub&dest=-1257786&spp=30&nm={$product_id}";

        try {
            $response = Http::get($url);
            if ($response->ok()) {
                $data = $response->json();

                if (isset($data['data']['products']) && count($data['data']['products']) > 0) {
                    $product = $data['data']['products'][0];

                    // Проверяем, существует-ли магазин
                    if ($product['supplierId'] == null) {
                        Log::error('Магазин у товара '.$product_id.' не найден');
                        $response = $this->formatResponse('false', 'Магазин товара не найден', 404);

                        return $this->sendResponse($response);
                    }

                    // Кешируем на 10 мин, что бы не делать лишних запросов
                    Cache::put($cacheKey, $product, 600);
                    return $product;
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при получении товара: '.$e->getMessage());
            $response = $this->formatResponse('false', 'Ошибка при получении товара', 500);

            return $this->sendResponse($response);
        }
    }

    /**
     * Формирует данные о товаре и магазине
     * И возвращает их
     */
    private function prepareProductData(string $product_id): array
    {
        $check = $this->productCheck($product_id);
        if(!$check){
            return [
                'message' => 'Товар уже добавлен',
                'status'  => 'false'
            ];
        }

        $product     = $this->loadProductData($product_id);
        if($product != null){
            $getSupplier = $this->getSupplier($product['supplierId']);

            $shop        = [
                'supplier_id' => $product['supplierId'],
                'inn'         => $getSupplier['inn'] ?? null,
                'legal_name'  => $getSupplier['supplierName'] ?? null,
                'wb_name'     => $getSupplier['trademark'] ?? $getSupplier['supplierName'],
            ];

            $product = $this->formatProductData($product);

            if(empty($product)){
                return [
                    'message' => 'Товара нет в наличии',
                    'status'  => 'false'
                ];
            }

            return [
                'product' => $product,
                'shop'    => $shop,
            ];
        }
        return [
            'message' => 'Товар не найден',
            'status'  => 'false'
        ];
    }

    /**
     * Возвращает товар и магазин
     * По артикулу из ВБ
     */
    public function fetchProduct(string $product_id): \Illuminate\Http\JsonResponse
    {
        try {
            $prepareData = $this->prepareProductData($product_id);
            return response()->json($prepareData);
        } catch (\Exception $e) {
            \Log::channel('wb')->error('ОШИБКА ЗАГРУЗКИ ТОВАРА:'.$e->getMessage(), ['exception' => $e]);
            $response = $this->formatResponse('false', 'Ошибка получения товара', 500);

            return $this->sendResponse($response);
        }
    }

    /**
     * Формирует массив для создания товара
     */
    private function getProductFieldsArray($product): array
    {
        return [
            'name'               => $product['name'],
            'is_archived'        => false,
            'shop_id'            => auth()->check() ? auth('sanctum')->user()->shop?->id : null,
            'wb_id'              => $product['wb_id'],
            'category_id'        => $product['category_id'],
            'price'              => $product['price'],
            'discount'           => $product['discount'],
            'rating'             => $product['rating'],
            'quantity_available' => $product['quantity_available'],
            'supplier_id'        => $product['supplier_id'],
            'supplier_rating'    => $product['supplier_rating'],
            'description'        => $product['description'],
            'images'             => $product['images'],
            'brand'              => $product['brand'],
            'colors'             => $product['colors'] ?? [],
            'sizes'              => $product['sizes'] ?? [],
        ];
    }

    public function create($product_id, $shop_id): bool
    {
        $check = $this->productCheck($product_id);
        if(!$check){
            return false;
        }

        $wb = new WBService();
        try{
            $prepareData = $wb->prepareProductData($product_id);
            $product     = $prepareData['product'];

            if(isset($prepareData['status'])){
                return false;
            }

            $productService = new ProductService;
            $createData     = $wb->getProductFieldsArray($product);
            $createData['shop_id'] = $shop_id;
            $createProduct  = $productService->create($createData);
            return true;
        }catch (\Exception $e){
            Log::error('Error adding product: '.$e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    public function createAllVariations(string $product_id): true
    {
        // Получаем все вариации
        $variationIds = $this->getAllVariations($product_id);

        // Удаляем сам product_id из массива (если он там есть)
        $variationIds = array_filter($variationIds, fn($id) => (string) $id !== (string) $product_id);

        $shopId = Product::where('wb_id', $product_id)->value('shop_id');
        if($shopId){
            LoadProductVariationsJob::dispatch($variationIds, $shopId)->delay(10);
            return true;
        }
        return false;
    }

    public function clearCategoryCache(Category $category)
    {
        $ids = collect();

        // Добавляем текущую категорию
        $ids->push($category->id);

        // Добавляем всех предков
        $ids = $ids->merge($category->getAllAncestorIds());

        // Добавляем всех потомков
        $ids = $ids->merge($category->getAllDescendantIds());

        // Удаляем кэш для всех этих категорий
        foreach ($ids->unique() as $id) {
            // Важно: если пагинация разная, можно или указать список страниц вручную, или динамически
            for ($page = 1; $page <= 5; $page++) { // можно увеличить лимит при необходимости
                Cache::forget("category_{$id}_ads_page_{$page}");
            }
        }
    }


    /**
     * Добавляет новый товар по артикулу из ВБ
     */
    public function addProduct(Request $request, string $product_id): JsonResponse
    {
        try {
            DB::beginTransaction();

//            $check = $this->productCheck($product_id);
//            if(!$check){
//                $response = $this->formatResponse('false', 'Товар уже добавлен', 403);
//                return $this->sendResponse($response);
//            }

            $prepareData = $this->prepareProductData($product_id);
            if(isset($prepareData['status'])){
                $response = $this->formatResponse('false', $prepareData['message'], 403);
                return $this->sendResponse($response);
            }

            $shop        = $prepareData['shop'];
            $product     = $prepareData['product'];
            $productCheck = $this->checkShop($shop);

            if(!$productCheck)
            {
                $response = $this->formatResponse('false', 'Данный товар принадлежит другому продавцу', 403);
                return $this->sendResponse($response);
            }
            $productService = new ProductService;
            $createData     = $this->getProductFieldsArray($product);
            $createProduct  = $productService->create($createData);
            $response       = $this->formatResponse('true', $createProduct['product'], 201, 'product');

            $category = Category::find($createData['category_id']);
            if($category != null) {
                $this->clearCategoryCache($category);
            }
            Cache::forget('categories_index');

            if($request->loadRelated == true) {
                $this->createAllVariations($product_id);
            }

            DB::commit();

//            if($request->loadRelated == true) {
//                $this->createAllVariations($product_id);
//            }

            return $this->sendResponse($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::channel('wb')->error('ОШИБКА ДОБАВЛЕНИЯ ТОВАРА:'.$e->getMessage(), ['exception' => $e]);
            $response = $this->formatResponse('false', 'Ошибка, попробуйте еще раз', $e->getCode());

            return $this->sendResponse($response);
        }
    }
}
