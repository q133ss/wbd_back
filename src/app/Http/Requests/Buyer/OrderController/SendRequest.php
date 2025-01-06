<?php

namespace App\Http\Requests\Buyer\OrderController;

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
            'text' => 'string|required',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'file_type' => 'nullable|in:order,barcode,review|required_if:file,present'
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Сообщение обязательно для заполнения.',
            'text.string' => 'Сообщение должно быть строкой.',
            'file.file' => 'Файл должен быть правильного формата.',
            'file.mimes' => 'Файл должен быть изображением в формате: jpeg, png, jpg, gif, svg.',
            'file.max' => 'Размер файла не должен превышать 2 МБ.',
            'file_type.required_if' => 'Тип файла обязателен, если файл загружен.',
            'file_type.in' => 'Тип файла должен быть одним из следующих: order, barcode, review.',
            'file_type.nullable' => 'Тип файла может быть пустым, если файл не загружен.',
        ];
    }
}
