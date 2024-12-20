<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductService
{
    private array $requiredFields;

    public function __construct()
    {
        // Обязательные поля для создания товара
        $this->requiredFields = [
            'name',
            'price',
            'cashback_percent',
            'discount',
            'rating',
            'quantity_available',
            'supplier_id',
            'supplier_rating',
            'is_archived'
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data)
    {
        // Проверяем наличие всех ключей
        $missingFields = array_diff($this->requiredFields, array_keys($data));

        if (!empty($missingFields)) {
            \Log::error("Ошибка создания товара: отсутствуют поля: " . implode(', ', $missingFields));

            return [
                'status' => 'error',
                'message' => 'Отсутствуют обязательные поля',
                'missing_fields' => $missingFields,
                'code' => 422
            ];
        }

        try{
            if(!isset($data['user_id'])){
                $data['user_id'] = auth('sanctum')->id();
            }
            $product = Product::create($data);
            return [
                'status' => 'success',
                'product' => $product,
                'code' => 201
            ];
        }catch (\Exception $e){
            \Log::error("Ошибка создания товара: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Ошибка создания товара',
                'code' => 500
            ];
        }
    }
}
