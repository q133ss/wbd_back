<?php

namespace App\Http\Requests\ProfileController;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
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
                'min:100',
                'max:100000',
                function(string $attribute, mixed $value, Closure $fail): void
                {
                    if ($value > auth('sanctum')->user()->balance) {
                        $fail('Недостаточно средств на счету.');
                    }
                }
            ],
            'card_number' => [
                'required',
                'regex:/^\d{16}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма обязательна для заполнения.',
            'amount.numeric' => 'Сумма должна быть числом.',
            'amount.min' => 'Минимальная сумма — 100.',
            'amount.max' => 'Максимальная сумма — 100000.',
            'card_number.required' => 'Номер карты обязателен.',
            'card_number.regex' => 'Номер карты должен состоять из 16 цифр.',
        ];
    }
}
