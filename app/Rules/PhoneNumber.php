<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\+\d\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $value)) {
            $fail('Поле номер телефона должно соответствовать формату +7(999)999-99-99');
        }
    }
}
