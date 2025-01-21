<?php

namespace App\Services;

use App\Exceptions\JsonException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
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
    public function productCheck(string $id): ?array
    {
        if (Product::where('wb_id', $id)->exists()) {
            $this->sendError('Товар уже добавлен', 403);
        }

        return null;
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
     * @return void
     */
    private function checkShop($shop, $product)
    {
        $user = Auth('sanctum')->user();

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
            if ($shop['supplier_id'] != $user->shop?->supplier_id) {
                $response = $this->formatResponse('false', 'Данный товар принадлежит другому продавцу', 403);

                return $this->sendResponse($response);
            }
        }
    }

    /**
     * Генерирует URL-адреса изображений для товара Wildberries.
     *
     * @param array $product Массив с ключами 'wb_id' (артикул товара) и 'pics' (количество изображений).
     * @return array Массив URL-адресов изображений.
     */
    private function generateImageUrls(array $product): array
    {
        $images = [];
        $wbId = (int) $product['wb_id'];
        $picsCount = $product['pics'] ?? 0;

        $vol = (int) floor($wbId / 100000); // Считаем vol
        $part = (int) floor($wbId / 1000); // Считаем part
        $host = ''; // Инициализация host

        // Определяем host на основе vol
        if ($vol >= 0 && $vol <= 143) $host = '01';
        elseif ($vol >= 144 && $vol <= 287) $host = '02';
        elseif ($vol >= 288 && $vol <= 431) $host = '03';
        elseif ($vol >= 432 && $vol <= 719) $host = '04';
        elseif ($vol >= 720 && $vol <= 1007) $host = '05';
        elseif ($vol >= 1008 && $vol <= 1061) $host = '06';
        elseif ($vol >= 1062 && $vol <= 1115) $host = '07';
        elseif ($vol >= 1116 && $vol <= 1169) $host = '08';
        elseif ($vol >= 1170 && $vol <= 1313) $host = '09';
        elseif ($vol >= 1314 && $vol <= 1601) $host = '10';
        elseif ($vol >= 1602 && $vol <= 1655) $host = '11';
        elseif ($vol >= 1656 && $vol <= 1919) $host = '12';
        elseif ($vol >= 1920 && $vol <= 2045) $host = '13';
        elseif ($vol >= 2046 && $vol <= 2189) $host = '14';
        elseif ($vol >= 2190 && $vol <= 2405) $host = '15';
        elseif ($vol >= 2406 && $vol <= 2621) $host = '16';
        elseif ($vol >= 2622 && $vol <= 2837) $host = '17';
        else $host = '18';

        // Генерируем ссылки на изображения
        for ($i = 1; $i <= $picsCount; $i++) {
            $images[] = "https://basket-{$host}.wbbasket.ru/vol{$vol}/part{$part}/{$wbId}/images/big/{$i}.webp";
        }

        return $images;
    }

    private function fetchDescription($product_id)
    {
        // Нужно найти способ получить описание!
        return null;
    }

    /**
     * Ищет категорию и возвращает ее
     */
    private function makeCategory(string $subject_id): mixed
    {
        return Category::where('id', $subject_id)
            ->orWhere('name', 'Без категории')
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

        return [
            'wb_id'              => $product['id'],
            'name'               => $product['name'],
            'price'              => $product['salePriceU'] / 100,
            'brand'              => $product['brand']      ?? null,
            'discount'           => $product['sale']       ?? 0,
            'rating'             => $product['rating']     ?? 0,
            'quantity_available' => $product['volume']     ?? 0,
            'supplier_id'        => $product['supplierId'] ?? null,
            'images'             => $this->generateImageUrls($product),
            'description'        => $this->fetchDescription($product['id']),
            'supplier_rating'    => $product['supplierRating'] ?? 0,
            'category_id'        => $this->makeCategory($product['subjectId']),
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

        $this->productCheck($product_id);

        $url = "https://card.wb.ru/cards/v1/detail?appType=1&curr=rub&dest=-1257786&spp=30&nm={$product_id}";

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
        $product     = $this->loadProductData($product_id);
        $getSupplier = $this->getSupplier($product['supplierId']);
        $shop        = [
            'supplier_id' => $product['supplierId'],
            'inn'         => $getSupplier['inn'],
            'legal_name'  => $getSupplier['supplierName'],
            'wb_name'     => $getSupplier['trademark'],
        ];

        $product = $this->formatProductData($product);

        return [
            'product' => $product,
            'shop'    => $shop,
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
            'name'               => $product['title'],
            'is_archived'        => false,
            'shop_id'            => auth('sanctum')->user()->shop?->id,
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
        ];
    }

    /**
     * Добавляет новый товар по артикулу из ВБ
     */
    public function addProduct(string $product_id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $prepareData = $this->prepareProductData($product_id);
            $shop        = $prepareData['shop'];
            $product     = $prepareData['product'];
            $this->productCheck($product_id);
            $this->checkShop($shop, $product);
            $productService = new ProductService;
            $createData     = $this->getProductFieldsArray($product);
            $createProduct  = $productService->create($createData);
            $response       = $this->formatResponse('true', $createProduct['product'], 201, 'product');
            DB::commit();

            return $this->sendResponse($response);
        } catch (\Exception $e) {
            Log::error('Error adding product: '.$e->getMessage(), ['exception' => $e]);
            $response = $this->formatResponse('false', $e->getMessage(), $e->getCode());

            return $this->sendResponse($response);
            DB::rollBack();
        }
    }
}
