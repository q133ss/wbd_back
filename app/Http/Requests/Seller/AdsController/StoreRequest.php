<?php

namespace App\Http\Requests\Seller\AdsController;

use App\Models\Ad;
use App\Models\Product;
use App\Models\Tariff;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $user = auth('sanctum')->user();

        return [
            'product_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $checkProduct = $user->checkProduct($value);
                    if (! $checkProduct) {
                        $fail('Указан неверный товар');
                    }

                    if (Ad::where('product_id', $value)->where('status', true)->exists()) {
                        $fail('У вас уже есть активное объявление с этим товаром');
                    }
                },
            ],
            'name'                    => 'required|string|max:255',
            'cashback_percentage'     => 'required|min:0|max:100',
            'order_conditions'        => 'required|string',
            'redemption_instructions' => 'required|string',
            'review_criteria'         => 'required|string',
            'redemption_count'        => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    // ищем все объявления юзера, если есть активное с таким количеством выкупов, то ошибка
                    $ads = $user->ads()->get();

                    // Считаем общее количество выкупов у уже созданных объявлений
                    $currentCount = $ads->sum(function ($ad) {
                        $keywords = $ad->keywords ?? [];
                        if (is_string($keywords)) {
                            $keywords = json_decode($keywords, true);
                        }

                        if (!empty($keywords)) {
                            // Если есть keywords — берём только их redemption_count
                            return collect($keywords)
                                ->sum(fn ($keyword) => (int) ($keyword['redemption_count'] ?? 0));
                        }

                        // Если keywords нет — берём обычный redemption_count
                        return (int) $ad->redemption_count;
                    });

                    // Подсчёт для нового объявления
                    $newCount = 0;
                    if (!empty($this->keywords) && is_array($this->keywords)) {
                        // Если переданы keywords — берём только их
                        $newCount = collect($this->keywords)
                            ->sum(fn ($keyword) => (int) ($keyword['redemption_count'] ?? 0));
                    } else {
                        // Если keywords нет — берём обычный redemption_count
                        $newCount = (int) $value;
                    }

                    // Итоговое количество
                    $totalCount = $currentCount + $newCount;

                    // Проверка на лимит тарифа
                    if (
                        $user->tariffs()?->wherePivot('status', true)->pluck('name')->first() === Tariff::TRIAL_PLAN
                        && $totalCount > Tariff::TRIAL_PLAN_COUNT
                    ) {
                        $fail('Вы не можете создавать объявления с количеством выкупов более ' . Tariff::TRIAL_PLAN_COUNT . ', так как у вас пробный тариф');
                    }
                }
            ],
            'one_per_user' => 'nullable|boolean',
            'color'        => 'nullable|array|max:50',
            'size'         => 'nullable|array|max:50',
            'keywords' => 'nullable|array',
            'keywords.*.word' => 'required|string|max:50',
            'keywords.*.redemption_count' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'              => 'Необходимо указать товар.',
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
            'redemption_count.required'        => 'Укажите количество выкупов.',
            'redemption_count.integer'         => 'Количество выкупов должно быть числом.',
            'redemption_count.min'             => 'Количество выкупов должно быть не менее 1.',
            'one_per_user.boolean'             => 'Поле "Один на товар на покупателя" неверное.',

            'keywords.array' => 'Поле ключевых слов должно быть массивом.',
            'keywords.*.word.required' => 'Каждое ключевое слово обязательно для заполнения.',
            'keywords.*.word.string' => 'Ключевое слово должно быть строкой.',
            'keywords.*.word.max' => 'Ключевое слово не должно превышать :max символов.',
            'keywords.*.redemption_count.required' => 'Поле "Количество выкупов" обязательно.',
            'keywords.*.redemption_count.integer' => 'Количество выкупов должно быть целым числом.',
            'keywords.*.redemption_count.min' => 'Количество выкупов не может быть меньше :min.',

            'color.array' => 'Цвет должен быть массивом.',
            'color.max' => 'Максимальное количество цветов - :max.',
            'size.array' => 'Размер должен быть массивом.',
            'size.max' => 'Максимальное количество размеров - :max.',
        ];
    }
}
