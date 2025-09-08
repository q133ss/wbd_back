<?php

namespace App\Http\Requests\AuthController;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'reset_token' => 'required|exists:users,reset_token',
            'password'    => 'required|string|min:8|confirmed'
        ];
    }

    public function messages(): array
    {
        return [
            'reset_token.required' => 'Произошла ошибка, попробуйте еще раз. (код: 1)',
            'reset_token' => 'Произошла ошибка, попробуйте еще раз. (код: 2)',
            'reset_token.exists' => 'Произошла ошибка, попробуйте еще раз. (код: 3)',
            'password.required' => 'Введите новый пароль.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];
    }
}
