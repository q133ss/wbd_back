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
        ];
    }
}
