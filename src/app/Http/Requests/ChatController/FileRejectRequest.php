<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class FileRejectRequest extends FormRequest
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
            'comment' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Укажите комментарий',
            'comment.string' => 'Комментарий должен быть строкой',
            'comment.max' => 'Комментарий не должен превышать 255 символов'
        ];
    }
}
