<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class SendRequest extends FormRequest
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
            'text'    => 'required|string',
            'files'   => 'nullable|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,gif|max:51200',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Введите сообщение',
            'text.string'   => 'Сообщение должно быть строкой',
            'files.array'   => 'Файлы должны быть массивом',
            'files.*.image' => 'Файл должен быть изображением',
            'files.*.mimes' => 'Файл должен быть изображением в форматах: jpeg, png, jpg, gif',
            'files.*.max'   => 'Размер файла не должен превышать 50 МБ',
        ];
    }
}
