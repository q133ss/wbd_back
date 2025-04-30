<?php

namespace App\Http\Requests\AuthController;

use App\Models\Role;
use App\Models\User;
use App\Rules\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends FormRequest
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
            'phone'    => ['required', 'max:255', 'exists:users,phone', new PhoneNumber],
            'password' => [
                'required',
                'max:255',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = User::where('phone', $this->phone)->first();
                    if (! $user || ! Hash::check($value, $user->password)) {
                        $fail('Неправильный номер телефона или пароль');
                    }
                },
            ],
            'role_id'  => [
                'nullable',
                'exists:roles,id',
                function ($attribute, $value, Closure $fail): void {
                    if (! in_array($value, Role::USER_ROLES)) {
                        $fail('Роль не найдена');
                    }
                }
            ],
            'remember' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите номер телефона',
            'phone.max'      => 'Поле номер телефона не должно превышать 255 символов',
            'phone.exists'   => 'Пользователь с таким телефоном не найден',

            'password.required' => 'Укажите пароль',
            'password.max'      => 'Поле пароль не должно превышать 255 символов',
            'password.string'   => 'Пароль должен быть строкой',

            'role_id.exists' => 'Роль не найдена',

            'remember.boolean' => 'Поле запомнить меня должно быть булевым значением',
        ];
    }
}
