<?php

namespace App\Http\Requests\AuthController;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ResetRequest extends FormRequest
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
            'phone'    => ['required', 'max:255', 'exists:phone_verifications,phone_number', new PhoneNumber],
            'code'     => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите номер телефона',
            'phone.max'      => 'Поле номер телефона не должно превышать 255 символов',
            'phone.exists'   => 'Пользователь с таким телефоном не найден',

            'code.required' => 'Укажите код',
            'code.string'   => 'Код должен быть строкой',

            'password.required'  => 'Укажите пароль',
            'password.string'    => 'Пароль должен быть строкой',
            'password.min'       => 'Пароль должен быть не менее 8 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }
}
