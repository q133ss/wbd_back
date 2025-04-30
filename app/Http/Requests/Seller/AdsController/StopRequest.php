<?php

namespace App\Http\Requests\Seller\AdsController;

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
            'ad_ids' => [
                'required',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $check = auth('sanctum')->user()->checkAd($value);
                    if (! $check) {
                        $fail('Указаны неверные объявления');
                    }
                },
            ],
            'ad_ids.*' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'ad_ids.required' => 'Выберите объявления',
            'ad_ids.array'    => 'Объявления должны быть массивом',

            'ad_ids.*.required' => 'Укажите объявление',
            'ad_ids.*.integer'  => 'Объявление должно быть числом',
        ];
    }
}
