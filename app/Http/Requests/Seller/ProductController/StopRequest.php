<?php

namespace App\Http\Requests\Seller\ProductController;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StopRequest extends FormRequest
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
        return [
            'product_ids' => [
                'required',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $check = auth('sanctum')->user()->checkProducts($value);
                    if (! $check) {
                        $fail('Указаны неверные товары');
                    }
                },
            ],
            'product_ids.*' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'Выберите товары',
            'product_ids.array'    => 'Товары должны быть массивом',

            'product_ids.*.required' => 'Укажите товар',
            'product_ids.*.integer'  => 'Товар должен быть числом',
        ];
    }
}
