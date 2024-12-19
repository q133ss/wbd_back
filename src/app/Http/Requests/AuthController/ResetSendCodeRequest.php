<?php

namespace App\Http\Requests\AuthController;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ResetSendCodeRequest extends FormRequest
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
            'phone' => ['required', 'max:255', 'exists:users,phone', new PhoneNumber],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите номер телефона',
            'phone.max'      => 'Поле номер телефона не должно превышать 255 символов',
            'phone.exists'   => 'Пользователь с таким телефоном не существует',
        ];
    }
}
