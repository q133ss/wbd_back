<?php

namespace App\Http\Requests\AuthController;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRequest extends FormRequest
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
            'name'     => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $isSeller = auth('sanctum')->user()->role?->slug === 'seller';
                    if ($isSeller && !$this->email) {
                        $fail('Email обязателен для заполнения для продавцов.');
                    }
                }
            ],
            'email'    => [
                'nullable',
                'string',
                'max:255',
                'email',
                'unique:users,email'
            ],
            'password' => 'required|string|min:8|max:255|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Имя обязательно для заполнения',
            'name.max'           => 'Поле имя не должно превышать 255 символов',
            'email.email'        => 'Укажите правильный email',
            'email.unique'       => 'Пользователь с таким email уже существует',
            'password.required'  => 'Пароль обязательно для заполнения',
            'password.min'       => 'Пароль должен быть не менее 8 символов',
            'password.max'       => 'Поле пароль не должно превышать 255 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }
}
