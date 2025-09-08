<?php

namespace App\Http\Requests\AuthController;

use Illuminate\Foundation\Http\FormRequest;

class ResetSendLinkRequest extends FormRequest
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
            'for_seller' => 'required|boolean',
            'phone'      => ['required', 'exists:users,phone']
        ];
    }

    public function messages(): array
    {
        return [
            'for_seller.required' => 'Ошибка (код 1)',
            'for_seller.boolean' => 'Ошибка (код 2)',

            'phone.required' => 'Поле телефон обязательно для заполнения',
            'phone.exists' => 'Пользователь с таким номером не найден'
        ];
    }
}
