<?php

namespace App\Http\Requests\ProfileController;

use Illuminate\Foundation\Http\FormRequest;

class TopUpRequest extends FormRequest
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
            'amount' => 'required|numeric|min:100'
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Поле "Сумма" обязательно для заполнения',
            'amount.numeric' => 'Поле "Сумма" должно быть числом',
            'amount.min' => 'Минимальная сумма пополнения 100 рублей'
        ];
    }
}
