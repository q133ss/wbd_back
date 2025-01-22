<?php

namespace App\Http\Requests\ProfileController;

use Illuminate\Foundation\Http\FormRequest;

class AvatarRequest extends FormRequest
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
            'avatar' => 'required|file|mimes:jpeg,png,jpg,gif,svg',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Выберите файл',
            'avatar.file'     => 'Аватар должен быть файлом.',
            'avatar.mimes'    => 'Аватар должен быть файлом одного из следующих типов: jpeg, png, jpg, gif, svg.',
        ];
    }
}
