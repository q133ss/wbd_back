<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;

class ProductService extends BaseService
{
    private array $requiredFields;

    public function __construct()
    {
        // Обязательные поля для создания товара
        $this->requiredFields = [
            'name',
            'price',
            'discount',
            'rating',
            'quantity_available',
            'supplier_id',
            'supplier_rating',
            'is_archived',
            'shop_id',
            'wb_id',
            'category_id',
        ];
    }

    public function create(array $data)
    {
        // Проверяем наличие всех ключей
        $missingFields = array_diff($this->requiredFields, array_keys($data));

        if (! empty($missingFields)) {
            Log::error('Ошибка создания товара: отсутствуют поля: '.implode(', ', $missingFields));

            return $this->formatResponse('false', 'Отсутствуют обязательные поля', 422);
        }

        try {
            if ($data['shop_id'] == null) {
                $data['shop_id'] = Shop::where('user_id', Auth('sanctum')->id())->pluck('id')->first();
            }
            //            if($data['category_id'] == null)
            //            {
            //                $data['category_id'] = (new Category())->getDefaultCategory();
            //            }
            $product = Product::create($data);

            return $this->formatResponse('true', $product, 201, 'product');
        } catch (\Exception $e) {
            Log::error('Ошибка создания товара: '.$e->getMessage());

            return $this->formatResponse('false', 'Ошибка создания товара', 500);
        }
    }
}
