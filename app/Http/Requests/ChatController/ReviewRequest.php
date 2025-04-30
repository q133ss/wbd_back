<?php

namespace App\Http\Requests\ChatController;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
            'rating' => 'required|numeric|min:1|max:5',
            'text'   => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Укажите рейтинг',
            'rating.numeric' => 'Рейтинг должен быть числом',
            'rating.min' => 'Рейтинг не может быть меньше 1',
            'rating.max' => 'Рейтинг не может быть больше 5',

            'text.required' => 'Укажите текст отзыва',
            'text.string' => 'Текст отзыва должен быть строкой',
            'text.max' => 'Текст отзыва не должен превышать 255 символов'
        ];
    }
}
