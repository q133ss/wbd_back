<?php

namespace App\Http\Requests\ProfileController;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
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
            'sbp' => [
                'nullable',
                'string',
                'max:255',
                new PhoneNumber(),
                function ($attribute, $value, $fail) {
                    if($value != null && $this->sbp_comment == null) {
                        $fail('Укажите банки для СБП.');
                    }
                }
            ],
            'sbp_comment' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if($value != null && $this->sbp == null) {
                        if($value != null && $this->sbp == null) {
                            $fail('Укажите номер телефона для СБП.');
                        }
                    }
                }
            ],
            'sber' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'tbank' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'ozon' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'alfa' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'vtb' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'raiffeisen' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'gazprombank' => ['nullable', 'string', 'regex:/^\d{16}$/']
        ];
    }

    public function messages(): array
    {
        return [
            'sbp.string' => 'Поле СБП должно быть строкой.',
            'sbp.max' => 'Поле СБП не должно превышать 255 символов.',
            'sbp_comment.string' => 'Комментарий к СБП должен быть строкой.',
            'sbp_comment.max' => 'Комментарий к СБП не должен превышать 255 символов.',

            'sber.regex' => 'Номер карты Сбербанка должен содержать ровно 16 цифр.',
            'tbank.regex' => 'Номер карты Тинькофф должен содержать ровно 16 цифр.',
            'ozon.regex' => 'Номер карты Ozon должен содержать ровно 16 цифр.',
            'alfa.regex' => 'Номер карты Альфа-Банк должен содержать ровно 16 цифр.',
            'vtb.regex' => 'Номер карты ВТБ должен содержать ровно 16 цифр.',
            'raiffeisen.regex' => 'Номер карты Райффайзен должен содержать ровно 16 цифр.',
            'gazprombank.regex' => 'Номер карты Газпромбанк должен содержать ровно 16 цифр.'
        ];
    }
}
