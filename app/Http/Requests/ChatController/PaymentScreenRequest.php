<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class PaymentScreenRequest extends FormRequest
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
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:51200'
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Прикрепите изображение',
            'file.image' => 'Неверный формат изображения',
            'file.mines' => 'Изображение должно быть в формате: jpeg, png, jpg, gif',
            'file.max'        => 'Размер файла не должен превышать 50 МБ.',
        ];
    }
}
