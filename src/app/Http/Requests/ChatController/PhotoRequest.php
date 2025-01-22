<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class PhotoRequest extends FormRequest
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
            'files' => 'required|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,gif|max:51200',
            'file_type' => 'required|in:send_photo,review' // Заказ сделан, оставил отзыв
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Загрузите фотографии.',
            'files.*.image' => 'Файл должен быть изображением.',
            'files.*.mimes' => 'Файл должен быть изображением в форматах: jpeg, png, jpg, gif.',
            'files.*.max' => 'Размер файла не должен превышать 50 МБ.',
            'file_type.required' => 'Тип файла обязателен.',
            'file_type.in' => 'Тип файла должен быть одним из следующих: order_created, review.'
        ];
    }
}
