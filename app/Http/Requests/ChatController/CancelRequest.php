<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
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
            'comment' => [
                !auth('sanctum')->user()->isSeller() ? 'nullable' : 'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Укажите комментарий',
            'comment.string' => 'Комментарий должен быть строкой'
        ];
    }
}
