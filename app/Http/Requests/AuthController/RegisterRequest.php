<?php

namespace App\Http\Requests\AuthController;

use App\Models\Role;
use App\Models\User;
use App\Rules\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'phone' => [
                'required',
                new PhoneNumber,
                Rule::unique('users', 'phone')
                    ->where(fn ($query) => $query->where('role_id', $this->role_id)),
            ],
            'email' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    // Проверяем уникальность только если email указан
                    if (! empty($value)) {
                        $exists = User::where('email', $value)
                            ->where('role_id', $this->role_id)
                            ->exists();

                        if ($exists) {
                            $fail('Пользователь с таким email уже существует');
                        }
                    }
                },
            ],
            'name'     => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $roles          = Role::whereIn('slug', ['seller', 'buyer'])->get();
                    $sellerRole     = $roles->firstWhere('slug', 'seller');
                    $allowedRoleIds = $roles->pluck('id')->toArray();

                    if (! in_array($value, $allowedRoleIds)) {
                        $fail('Указана неверная роль');
                    }

                    // Дополнительная проверка для email продавца
                    if ($value == $sellerRole->id && empty(request()->input('email'))) {
                        $fail('Укажите email');
                    }
                },
            ],
            'utm_source'   => 'nullable|string|max:255',
            'utm_medium'   => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_content'  => 'nullable|string|max:255',
            'utm_term'     => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Телефон обязателен для заполнения',
            'phone.unique'   => 'Пользователь с таким телефоном уже существует',

            'email.required_if' => 'Email обязателен для продавцов',
            'email.email'       => 'Введите корректный email адрес',
            'email.unique'      => 'Пользователь с таким email уже существует',

            'name.required' => 'Имя обязательно для заполнения',
            'name.string'   => 'Имя должно быть строкой',
            'name.max'      => 'Имя не должно превышать 255 символов',

            'password.required'  => 'Пароль обязателен для заполнения',
            'password.string'    => 'Пароль должен быть строкой',
            'password.min'       => 'Пароль должен содержать минимум 8 символов',
            'password.confirmed' => 'Подтверждение пароля не совпадает',

            'role_id.required' => 'Роль пользователя обязательна',
            'role_id.exists'   => 'Выбранная роль не существует',
        ];
    }
}
