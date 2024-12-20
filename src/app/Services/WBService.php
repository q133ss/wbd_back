<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WBService extends BaseService
{
    public function productCheck(string $id): array
    {
        $check = Product::where('wb_id', $id)->exists();

        if ($check) {
            return $this->formatResponse('false', 'Товар уже добавлен', 403);
        }
        return $this->formatResponse('true', 'Товар еще не создан', 200);
    }

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

    private function checkShop($user, $supplier_id){
        if($user->shop?->supplier_id != null){
            if($supplier_id != $user->shop?->supplier_id)
            {
                return $this->formatResponse('false', 'Данный товар принадлежит другому продавцу', 403);
            }
            return $this->formatResponse('true', $user->shop, 200);
        }else{
            $supplier = $this->getSupplier($supplier_id);
            if(!$supplier){
                return $this->formatResponse('false', 'Компания не найдена', 404);
            }
            $shop = Shop::create([
                'user_id' => $user->id,
                'supplier_id' => $supplier_id,
                'inn' => $supplier['inn'],
                'legal_name' => $supplier['supplierName'],
                'wb_name' => $supplier['trademark']
            ]);
            return $this->formatResponse('true', $shop, 200);
        }
        return true;
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

    public function fetchProduct(string $product_id)
    {
        $check = $this->productCheck($product_id);
        if($check['status'] == 'false'){
            return $check;
        }

        $url = "https://card.wb.ru/cards/v1/detail?appType=1&curr=rub&dest=-1257786&spp=30&nm={$product_id}";
        try{
            $response = Http::get($url);

            if ($response->ok()) {
                $data = $response->json();

                if (isset($data['data']['products']) && count($data['data']['products']) > 0) {
                    $product = $data['data']['products'][0];

                    // Проверяем, существует-ли магазин
                    if($product['supplierId'] == null)
                    {
                        Log::error('Магазин у товара '.$product_id.' не найден');
                        return $this->formatResponse('false', 'Магазин не найден', 404);
                    }

                    $getSupplier = $this->getSupplier($product['supplierId']);
                    $shop = [
                        'supplier_id' => $product['supplierId'],
                        'inn' => $getSupplier['inn'],
                        'legal_name' => $getSupplier['supplierName'],
                        'wb_name' => $getSupplier['trademark']
                    ];

                    $product = $this->formatProductData($product);

                    $response = $this->formatResponse('true', ['product' => $product, 'shop' => $shop], 200);
                    return $this->sendResponse($response);
                }
            }
        }catch (\Exception $e){
            Log::error('Ошибка при получении товара: '.$e->getMessage());
            $response = $this->formatResponse('false', 'Ошибка при получении товара', 500);
            return $this->sendResponse($response);
        }
    }
}
