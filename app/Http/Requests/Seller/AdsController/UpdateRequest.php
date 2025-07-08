<?php

namespace App\Http\Requests\Seller\AdsController;

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
        $user = Auth('sanctum')->user();

        return [
            'name'                    => 'required|string|max:255',
            'cashback_percentage'     => 'required|min:0|max:100',
            'order_conditions'        => 'required|string',
            'redemption_instructions' => 'required|string',
            'review_criteria'         => 'required|string',
            'one_per_user'            => 'nullable|boolean',
            'is_archived'             => 'nullable|min:0|max:1',
            'color'        => 'nullable|string|max:50',
            'size'         => 'nullable|string|max:50',
            'keywords' => 'nullable|array',
            'keywords.*.word' => 'required|string|max:50',
            'keywords.*.redemption_count' => 'required|integer|min:1'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                    => 'Название обязательно для заполнения.',
            'name.string'                      => 'Название должно быть строкой.',
            'name.max'                         => 'Название не может быть длиннее 255 символов.',
            'cashback_percentage.required'     => 'Необходимо указать процент кэшбэка.',
            'cashback_percentage.min'          => 'Процент кэшбэка не может быть меньше 0.',
            'cashback_percentage.max'          => 'Процент кэшбэка не может превышать 100.',
            'order_conditions.required'        => 'Укажите условия заказа.',
            'order_conditions.string'          => 'Условия заказа должны быть строкой.',
            'redemption_instructions.required' => 'Укажите инструкции для выкупа.',
            'redemption_instructions.string'   => 'Инструкции должны быть строкой.',
            'review_criteria.required'         => 'Критерии отзыва обязательны для заполнения.',
            'review_criteria.string'           => 'Критерии отзыва должны быть строкой.',
            'redemption_count.min'             => 'Количество выкупов должно быть не менее 1.',
            'one_per_user.boolean'             => 'Поле "Один на товар на покупателя" неверное.',
            'is_archived.min'                  => 'Поле "архивный" должно быть не меньше 0',
            'is_archived.max'                  => 'Поле "архивный" должно быть не больше 1',

            'keywords.array' => 'Поле ключевых слов должно быть массивом.',
            'keywords.*.word.required' => 'Каждое ключевое слово обязательно для заполнения.',
            'keywords.*.word.string' => 'Ключевое слово должно быть строкой.',
            'keywords.*.word.max' => 'Ключевое слово не должно превышать :max символов.',
            'keywords.*.redemption_count.required' => 'Поле "Количество выкупов" обязательно.',
            'keywords.*.redemption_count.integer' => 'Количество выкупов должно быть целым числом.',
            'keywords.*.redemption_count.min' => 'Количество выкупов не может быть меньше :min.',
        ];
    }
}
