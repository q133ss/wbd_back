<?php

namespace App\Http\Requests\TelegramController;

use Illuminate\Contracts\Validation\Validator;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'telegram_id' => 'required',
            'phone' => 'required',
            'role' => 'required|string|in:seller,buyer',
            'chatId' => 'nullable|string',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.required' => 'Идентификатор Telegram обязателен для заполнения',
            'telegram_id.unique' => 'Этот Telegram ID уже зарегистрирован',

            'phone.required' => 'Номер телефона обязателен для заполнения',

            'role.required' => 'Роль пользователя обязательна',
            'role.in' => 'Роль должна быть либо "seller", либо "buyer"',

            'chatId.required' => 'Идентификатор чата обязателен',

            'first_name.string' => 'Имя должно быть строкой',
            'first_name.max' => 'Имя не должно превышать 255 символов',

            'last_name.string' => 'Фамилия должна быть строкой',
            'last_name.max' => 'Фамилия не должно превышать 255 символов'
        ];
    }

    public function failedValidation(Validator $validator)
    {
        \Log::channel('tg')->error('Ошибка валидации Telegram RegisterRequest', [
            'input' => $this->all(),           // входные данные
            'errors' => $validator->errors(),  // ошибки валидации
        ]);
    }
}
