<?php

namespace App\Http\Requests\ProfileController;

use App\Models\Tariff;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopupBuybacksRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                Rule::in(Tariff::pluck('buybacks_count')->all()),
                function (string $attribute, mixed $value, Closure $fail): void
                {
                    $balance = auth('sanctum')->user()->balance;
                    $sum = Tariff::where('buybacks_count', $value)->pluck('price')->first();
                    if($balance - $sum < 0){
                        $fail('У вас недостаточно денег');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Поле "Сумма" обязательно для заполнения',
            'amount.numeric' => 'Поле "Сумма" должно быть числом',
            'amount.min' => 'Минимальная сумма пополнения 1 выкуп',
            'amount.in' => 'Указанно неверное количество выкупов'
        ];
    }
}
