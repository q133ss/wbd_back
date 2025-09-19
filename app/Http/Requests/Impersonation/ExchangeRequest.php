<?php

namespace App\Http\Requests\Impersonation;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:40', 'max:120'],
        ];
    }
}
