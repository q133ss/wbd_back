<?php

namespace App\Http\Requests\Seller\AdsController;

use App\Models\Ad;
use App\Models\Product;
use App\Models\Tariff;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth('sanctum')->user();

        return [
            'product_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $checkProduct = $user->checkProduct($value);
                    if (! $checkProduct) {
                        $fail('Указан неверный товар');
                    }

                    if (Ad::where('product_id', $value)->where('status', true)->exists()) {
                        $fail('У вас уже есть активное объявление с этим товаром');
                    }
                },
            ],
            'name'                    => 'required|string|max:255',
            'cashback_percentage'     => 'required|min:0|max:100',
            'order_conditions'        => 'required|string',
            'redemption_instructions' => 'required|string',
            'review_criteria'         => 'required|string',
            'redemption_count'        => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $priceBuybacks = 0;
                    if ($value > $user->redemption_count) {
                        // Если выкупов не хватает на балансе
                        $buybacksCount = $value - $user->redemption_count;
                        $priceBuybacks = $buybacksCount * config('price.buyback_price');
                    }

                    $productPrice       = Product::find($this->product_id)->pluck('price')->first();
                    $cashbackPercentage = $this->cashback_percentage;
                    $cashbackAmount = ($productPrice * $cashbackPercentage) / 100;
                    $price_with_cashback = $productPrice - $cashbackAmount;

                    $priceForUser = $price_with_cashback * $value;
                    $totalPrice = $priceBuybacks + $priceForUser;
                    $newBalance   = $user->balance - $totalPrice;

                    if($newBalance < 0){
                        //$fail('На балансе недостаточно средств');
                    }
                },
            ],
            'one_per_user' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'              => 'Необходимо указать товар.',
            'name.required'                    => 'Название обязательно для заполнения.',
            'name.string'                      => 'Название должно быть строкой.',
            'name.max'                         => 'Название не может быть длиннее 255 символов.',
            'cashback_percentage.required'     => 'Необходимо указать процент кэшбэка.',
            'cashback_percentage.min'          => 'Процент кэшбэка не может быть меньше 0.',
            'cashback_percentage.max'          => 'Процент кэшбэка не может превышать 100.',
            'order_conditions.required'        => 'Укажите условия заказа.',
            'order_conditions.string'          => 'Условия заказа должны быть строкой.',
            'redemption_instructions.required' => 'Укажите инструкции для выкупа.',
            'redemption_instructions.string'   => 'Инструкции должны быть строкой.',
            'review_criteria.required'         => 'Критерии отзыва обязательны для заполнения.',
            'review_criteria.string'           => 'Критерии отзыва должны быть строкой.',
            'redemption_count.required'        => 'Укажите количество выкупов.',
            'redemption_count.integer'         => 'Количество выкупов должно быть числом.',
            'redemption_count.min'             => 'Количество выкупов должно быть не менее 1.',
            'one_per_user.boolean'             => 'Поле "Один на товар на покупателя" неверное.',
        ];
    }
}
