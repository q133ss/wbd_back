<?php

namespace App\Http\Requests\AuthController;

use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
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
            'phone' => 'required|max:255|regex:/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/|unique:users,phone',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите номер телефона',
            'phone.max' => 'Поле номер телефона не должно превышать 255 символов',
            'phone.unique' => 'Пользователь с таким телефоном уже существует',
            'phone.regex' => 'Поле номер телефона должно соответствовать формату +7(999)999-99-99',
        ];
    }
}
