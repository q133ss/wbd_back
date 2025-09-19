<?php

namespace App\Http\Requests\Admin\Autoposting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled' => $this->toBoolean('is_enabled'),
            'show_price' => $this->toBoolean('show_price'),
            'show_cashback' => $this->toBoolean('show_cashback'),
            'show_conditions' => $this->toBoolean('show_conditions'),
            'show_photo' => $this->toBoolean('show_photo'),
            'show_link' => $this->toBoolean('show_link'),
        ]);
    }

    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'show_price' => ['required', 'boolean'],
            'show_cashback' => ['required', 'boolean'],
            'show_conditions' => ['required', 'boolean'],
            'show_photo' => ['required', 'boolean'],
            'show_link' => ['required', 'boolean'],
        ];
    }

    private function toBoolean(string $key): bool
    {
        $value = $this->input($key);

        if ($value === null) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
