<?php

namespace App\Http\Requests\Seller\PromocodeController;

use App\Models\Promocode;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ApplyRequest extends FormRequest
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
            'promocode' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $promocode = Promocode::where('promocode', $value);
                    if (! $promocode->exists()) {
                        $fail('Указан неверный промокод');
                    }

                    if ($promocode->exists()) {

                        $promocode = $promocode->first();

                        $currentDate = now();
                        if ($currentDate < $promocode->start_date || $currentDate > $promocode->end_date) {
                            $fail('Промокод неактивен');
                        }

                        // Проверьте лимиты использования
                        if ($promocode->users()->count() >= $promocode->max_usage) {
                            $fail('Максимальный лимит использований исчерпан');
                        }

                        $used = DB::table('promocode_user')
                            ->where('user_id', auth('sanctum')->id())
                            ->where('promocode_id', $promocode->id)
                            ->exists();
                        if ($used) {
                            $fail('Вы уже использовали этот промокод');
                        }

                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'promocode.required' => 'Укажите промокод',
            'promocode.string'   => 'Промокод должен быть строкой',
        ];
    }
}
