<?php

namespace App\Http\Requests\ProfileController;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:255', 'exists:users,phone', new PhoneNumber],
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.file' => 'Аватар должен быть файлом.',
            'avatar.mimes' => 'Аватар должен быть файлом одного из следующих типов: jpeg, png, jpg, gif, svg.',
            'name.required' => 'Поле имя обязательно для заполнения.',
            'name.string' => 'Имя должно быть строкой.',
            'name.max' => 'Имя не может быть длиннее 255 символов.',
            'phone.required' => 'Поле телефон обязательно для заполнения.',
            'phone.string' => 'Телефон должен быть строкой.',
            'phone.max' => 'Телефон не может быть длиннее 255 символов.',
            'phone.exists' => 'Телефон не найден в системе.',
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Неверный формат email.',
            'password.required' => 'Поле пароль обязательно для заполнения.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль должен быть не меньше 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.'
        ];
    }
}
