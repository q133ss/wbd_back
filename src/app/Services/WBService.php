<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WBService
{
    public function getSupplier($supplier_id)
    {
        try {
            $getSupplierUrl = "https://static-basket-01.wbbasket.ru/vol0/data/supplier-by-id/{$supplier_id}.json";
            $supplierResponse = Http::get($getSupplierUrl);
            return $supplierResponse->json();
        }catch (\Exception $e) {
            \Log::error('Ошибка при получении юо лица. '.$e->getMessage);
            return false;
        }
    }

    private function getShop(mixed $user, mixed $product): array
    {
        $shopArr = [];
        if($user->shop){
            if($product['supplierId'] != $user->shop?->supplier_id)
            {
                return [
                    'status' => 'false',
                    'message' => 'Данный товар принадлежит другому продавцу',
                    'code' => 403
                ];
            }

            $shopArr['supplier_id'] = $user->shop?->supplier_id;
            $shopArr['inn'] = $user->shop?->inn;
            $shopArr['legal_name'] = $user->shop?->legal_name;
            $shopArr['wb_name'] = $user->shop?->wb_name;
        }else{
            $supplier = $this->getSupplier($product['supplierId']);

            if($supplier)
            {
                $shopArr['supplier_id'] = $product['supplierId'];
                $shopArr['inn'] = $supplier['inn'];
                $shopArr['legal_name'] = $supplier['supplierName'];
                $shopArr['wb_name'] = $supplier['trademark'];
                $user->shop()->create($shopArr);
            }
        }
        return $shopArr;
    }

    private function formatProductData(array $product): array
    {
        return [
            'product_id' => $product['id'],
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
        ];
    }

    /**
     * Принимает в артикул wb, отдает массив с товаром
     * @param string $product_id
     * @return array|null
     */
    public function fetchProduct(string $product_id)
    {
        $url = "https://card.wb.ru/cards/v1/detail?appType=1&curr=rub&dest=-1257786&spp=30&nm={$product_id}";

        try {
            $response = Http::get($url);

            if ($response->ok()) {
                $data = $response->json();

                if (isset($data['data']['products']) && count($data['data']['products']) > 0) {
                    $product = $data['data']['products'][0];

                    if($product['supplierId'] == null)
                    {
                        Log::error('Магазин у товара '.$product_id.' не найден');
                        return [
                            'status' => 'false',
                            'message' => 'Магазин не найден',
                            'code' => 404
                        ];
                    }

                    $user = Auth('sanctum')->user();

                    $shopArr = $this->getShop($user, $product);
                    $product_arr = $this->formatProductData($product);

                    Cache::put('wb_product_'.$product_id, ['product' => $product_arr, 'shop' => $shopArr]);
                    return [
                        'status' => 'true',
                        'message' => ['product' => $product_arr, 'shop' => $shopArr],
                        'code' => 200
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("ОШИБКА ПРИ ЗАГРУЗКЕ ТОВАРОВ ИЗ WB: " . $e->getMessage());
            return [
                'status' => 'false',
                'message' => 'Произошла ошибка при загрузке товара',
                'code' => 500
            ];
        }

        // Тут имеет смысл вызывать какой нибудь другой метод, что бы попробовать еще раз получить его!
        return [
            'status' => 'false',
            'message' => 'Товар не найден',
            'code' => 404
        ];
    }

    private function generateImageUrls($product)
    {
        $images = [];
        $picsCount = $product['pics'] ?? 0;
        $shortId = floor($product['id'] / 100000);
        $basket = floor($shortId / 144);

        for ($i = 1; $i <= $picsCount; $i++) {
            $images[] = "https://basket-{$basket}.wbbasket.ru/vol{$shortId}/part" . floor($product['id'] / 1000) . "/{$product['id']}/images/big/{$i}.webp";
        }

        return $images;
    }

    private function fetchDescription($product_id)
    {
        $part = floor($product_id/1000);
        $basket = $padded = str_pad($finalBasket, 2, '0', STR_PAD_LEFT);
        $url = "https://basket-{$basket}.wbbasket.ru/vol{$short_id}/part{$part}/{$product_id}/info/ru/card.json";

        try {
            $response = Http::get($url);

            if ($response->ok()) {
                $data = $response->json();
                return $data['description'] ?? '';
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching description: " . $e->getMessage());
        }

        return null;
    }

    private function createProduct($productArr){
        $productService = new ProductService();
        $product = $productArr['product'];
        $data = [
            'name' => $product->title ?? 'Без названия',
            'price' => $product->price ?? 0,
            'cashback_percent' => 0,
            'discount' => $product->discount ?? 0,
            'rating' => $product->rating ?? 0,
            'images' => $this->generateImageUrls($product),
            'quantity_available' => $product->quantity_available ?? 0,
            'supplier_id' => $product->supplier_id ?? null,
            'supplier_rating' => $product->supplier_rating ?? 0,
            'is_archived' => false,
        ];
        $createdProduct = $productService->create($data);
        return Response()->json([
            'product' => $createdProduct,
            'shop' => $productArr['shop']
        ], 201);
    }

    public function addProduct(string $product_id)
    {
        // Проверяем кеш
        if (Cache::has('wb_product_'.$product_id)) {
            $product = Cache::get('wb_product_' . $product_id);
        }else{
            $product = $this->fetchProduct($product_id);
            if($product == null) {
                return Response()->json(['message' => 'Товар не найден'], 404);
            }
        }

        $createdProduct = $this->createProduct($product);
        if ($createdProduct['status'] == 'success') {
            return Response()->json(['message' => 'true','product' => $createdProduct], 201);
        }
        return Response()->json(['message' => $createdProduct['message']], $createdProduct['code']);

    }
}
