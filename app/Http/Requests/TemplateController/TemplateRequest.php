<?php

namespace App\Http\Requests\TemplateController;

use Illuminate\Foundation\Http\FormRequest;

class TemplateRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Поле текст обязательно для заполнения',
            'text.string'   => 'Поле текст должно быть строкой'
        ];
    }
}
