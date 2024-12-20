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
     * @param string $id
     * @return array|null
     * @throws JsonException
     */
    public function productCheck(string $id): array|null
    {
        if (Product::where('wb_id', $id)->exists()) {
            $this->sendError('Товар уже добавлен', 403);
        }
        return null;
    }

    public function getSupplier($supplier_id)
    {
        try {
            $getSupplierUrl = "https://static-basket-01.wbbasket.ru/vol0/data/supplier-by-id/{$supplier_id}.json";
            $supplierResponse = Http::get($getSupplierUrl);
            return $supplierResponse->json();
        }catch (\Exception $e) {
            \Log::error('Ошибка при получении юо лица. '.$e->getMessage);
            $response = $this->formatResponse('false', 'Ошибка при получении данных о магазине', 500);
            return $this->sendResponse($response);
        }
    }

    /**
     * @param $shop
     * @param $product
     * @return void
     */
    private function checkShop($shop, $product)
    {
        $user = Auth('sanctum')->user();

        // Если магазина нет, создаем новый
        if($user->shop == null)
        {
            Shop::create([
                'user_id' => $user->id,
                'supplier_id' => $shop['supplier_id'],
                'inn' => $shop['inn'],
                'legal_name' => $shop['legal_name'],
                'wb_name' => $shop['wb_name']
            ]);
            return true;
        }else{
            // Проверяем магазин
            if($shop['supplier_id'] != $user->shop?->supplier_id)
            {
                $response = $this->formatResponse('false', 'Данный товар принадлежит другому продавцу', 403);
                return $this->sendResponse($response);
            }
        }
    }

    /**
     * Принимает в себя товар из АПИ ВБ
     * Возвращает массив с фото
     *
     * @param $product
     * @return array
     */
    private function generateImageUrls($product): array
    {
        $images = [];
        $picsCount = $product['pics'] ?? 0;
        $shortId = floor($product['wb_id'] / 100000);
        $basket = floor($shortId / 144);

        for ($i = 1; $i <= $picsCount; $i++) {
            $images[] = "https://basket-{$basket}.wbbasket.ru/vol{$shortId}/part" . floor($product['id'] / 1000) . "/{$product['id']}/images/big/{$i}.webp";
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
     *
     * @param string $subject_id
     * @return mixed
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
     *
     * @param array $product
     * @return array
     */
    private function formatProductData(array $product): array
    {
        $product['wb_id'] = $product['id'];
        return [
            'wb_id' => $product['id'],
            'title' => $product['name'],
            'price' => $product['salePriceU'] / 100,
            'brand' => $product['brand'] ?? null,
            'discount' => $product['sale'] ?? 0,
            'rating' => $product['rating'] ?? 0,
            'quantity_available' => $product['volume'] ?? 0,
            'supplier_id' => $product['supplierId'] ?? null,
            'images' => $this->generateImageUrls($product),
            'description' => $this->fetchDescription($product['id']),
            'supplier_rating' => $product['supplierRating'] ?? 0,
            'category_id' => $this->makeCategory($product['subjectId'])
        ];
    }

    /**
     * Возвращает объект товара из АПИ ВБ
     *
     * @param string $product_id
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
                        Log::error('Магазин у товара ' . $product_id . ' не найден');
                        $response = $this->formatResponse('false', 'Магазин товара не найден', 404);
                        return $this->sendResponse($response);
                    }

                    // Кешируем на 10 мин, что бы не делать лишних запросов
                    Cache::put($cacheKey, $product, 600);

                    return $product;
                }
            }
        }catch (\Exception $e){
            Log::error('Ошибка при получении товара: '.$e->getMessage());
            $response = $this->formatResponse('false', 'Ошибка при получении товара', 500);
            return $this->sendResponse($response);
        }
    }

    /**
     * Формирует данные о товаре и магазине
     * И возвращает их
     *
     * @param string $product_id
     * @return array
     */
    private function prepareProductData(string $product_id): array
    {
        $product = $this->loadProductData($product_id);
        $getSupplier = $this->getSupplier($product['supplierId']);
        $shop = [
            'supplier_id' => $product['supplierId'],
            'inn' => $getSupplier['inn'],
            'legal_name' => $getSupplier['supplierName'],
            'wb_name' => $getSupplier['trademark']
        ];

        $product = $this->formatProductData($product);
        return [
            'product' => $product,
            'shop' => $shop
        ];
    }

    /**
     * Возвращает товар и магазин
     * По артикулу из ВБ
     *
     * @param string $product_id
     * @return JsonResponse
     */
    public function fetchProduct(string $product_id): \Illuminate\Http\JsonResponse
    {
        try{
            $prepareData = $this->prepareProductData($product_id);
            $response = $this->formatResponse('true', $prepareData, 200);
            return $this->sendResponse($response);

        }catch (\Exception $e){
            $response = $this->formatResponse('false', 'Ошибка получения товара', 500);
            return $this->sendResponse($response);
        }
    }

    /**
     * Формирует массив для создания товара
     *
     * @param $product
     * @return array
     */
    private function getProductFieldsArray($product): array
    {
        return [
            'name' => $product['title'],
            'is_archived' => false,
            'shop_id' => Auth()->user()->shop?->id,
            'wb_id' => $product['wb_id'],
            'category_id' => $product['category_id'],
            'price' => $product['price'],
            'discount' => $product['discount'],
            'rating' => $product['rating'],
            'quantity_available' => $product['quantity_available'],
            'supplier_id' => $product['supplier_id'],
            'supplier_rating' => $product['supplier_rating'],
            'description' => $product['description'],
            'images' => $product['images'],
            'brand' => $product['brand']
        ];
    }

    /**
     * Добавляет новый товар по артикулу из ВБ
     *
     * @param string $product_id
     * @return JsonResponse
     */
    public function addProduct(string $product_id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $prepareData = $this->prepareProductData($product_id);
            $shop = $prepareData['shop'];
            $product = $prepareData['product'];
            $this->productCheck($product_id);
            $this->checkShop($shop, $product);
            $productService = new ProductService();
            $createData = $this->getProductFieldsArray($product);
            $createProduct = $productService->create($createData);
            $response = $this->formatResponse('true', $createProduct['product'], 201, 'product');
            DB::commit();
            return $this->sendResponse($response);
        } catch (\Exception $e) {
            Log::error('Error adding product: ' . $e->getMessage(), ['exception' => $e]);
            $response = $this->formatResponse('false', $e->getMessage(), $e->getCode());
            return $this->sendResponse($response);
            DB::rollBack();
        }
    }
}
