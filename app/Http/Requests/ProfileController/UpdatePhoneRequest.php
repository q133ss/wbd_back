<?php

namespace App\Http\Requests\ProfileController;

use App\Models\PhoneVerification;
use App\Rules\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePhoneRequest extends FormRequest
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
            'phone'    => ['required', 'string', 'max:255', 'unique:users,phone', new PhoneNumber],
            'code'     => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    $check = PhoneVerification::where('phone_number', $this->phone)
                        ->where('verification_code', $value);
                    if(!$check->exists()) {
                        $fail('Код неверный');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Поле телефон обязательно для заполнения.',
            'phone.string'   => 'Телефон должен быть строкой.',
            'phone.max'      => 'Телефон не может быть длиннее 255 символов.',
            'phone.unique'   => 'Телефон уже зарегистрирован.',
            'phone.regex'    => 'Телефон должен быть в формате +7(999)999-99-99.',
            'code.required'  => 'Поле код обязательно для заполнения.',
            'code.string'    => 'Код должен быть строкой.',
            'code.max'       => 'Код не может быть длиннее 255 символов.'
        ];
    }
}
