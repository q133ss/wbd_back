@extends('layouts.main')

@section('title', 'Добавить промокод')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Добавить промокод</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.promocodes.store') }}" method="POST" id="promoForm">
                @csrf

                <!-- Общие поля -->
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Код</label>
                    <input type="text" name="promocode" class="form-control" value="{{ old('promocode') }}" required>
                    @error('promocode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата начала</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата окончания</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                        @error('end_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Сколько раз один юзер может использовать промокод?</label>
                    <input type="number" name="max_usage" class="form-control" value="{{ old('max_usage') }}">
                    @error('max_usage') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <!-- Тип промокода -->
                <div class="mb-3">
                    <label class="form-label">Тип промокода</label>
                    <select name="data[type]" id="promoType" class="form-control" required>
                        <option value="">Выберите тип</option>
                        <option value="custom_tariff" {{ old('data.type')==='custom_tariff'?'selected':'' }}>Кастомный тариф</option>
                        <option value="discount" {{ old('data.type')==='discount'?'selected':'' }}>Скидка</option>
                        <option value="extra_days" {{ old('data.type')==='extra_days'?'selected':'' }}>Дополнительные дни</option>
                        <option value="free_tariff" {{ old('data.type')==='free_tariff'?'selected':'' }}>Бесплатный тариф</option>
                    </select>
                    @error('data.type') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <!-- Discount -->
                <div class="mb-3 type-block" id="block-discount" style="display:none;">
                    <label class="form-label">Тариф</label>
                    <select name="data[tariff_name]" class="form-control" disabled>
                        @foreach($tariffs as $tariff)
                            <option value="{{ $tariff->name }}" {{ old('data.tariff_name')===$tariff->name?'selected':'' }}>
                                {{ $tariff->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Процент скидки</label>
                    <input type="number" name="data[discount_percent]" class="form-control" min="1" max="100"
                           value="{{ old('data.discount_percent') }}" disabled>
                </div>

                <!-- Extra Days -->
                <div class="mb-3 type-block" id="block-extra_days" style="display:none;">
                    <label class="form-label">Тариф</label>
                    <select name="data[tariff_name]" class="form-control" disabled>
                        @foreach($tariffs as $tariff)
                            <option value="{{ $tariff->name }}" {{ old('data.tariff_name')===$tariff->name?'selected':'' }}>
                                {{ $tariff->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Количество дней</label>
                    <input type="number" name="data[extra_days]" class="form-control" min="1"
                           value="{{ old('data.extra_days') }}" disabled>
                </div>

                <!-- Free Tariff -->
                <div class="mb-3 type-block" id="block-free_tariff" style="display:none;">
                    <label class="form-label">Тариф</label>
                    <select name="data[tariff_name]" class="form-control" id="tariffSelect" disabled>
                        <option value="">Выберите тариф</option>
                        @foreach($tariffs as $tariff)
                            <option
                                value="{{ $tariff->name }}"
                                data-variants='@json($tariff->data, JSON_UNESCAPED_UNICODE)'
                                {{ old('data.tariff_name')===$tariff->name?'selected':'' }}
                            >
                                {{ $tariff->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Вариант тарифа</label>
                    <select name="data[variant_name]" id="variantSelect" class="form-control" disabled>
                        <option value="">Сначала выберите тариф</option>
                    </select>
                    @error('data.variant_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <!-- Custom Tariff -->
                <div class="mb-3 type-block" id="block-custom_tariff" style="display:none;">
                    <label class="form-label">Еще не готово</label>
                    <textarea name="data[description]" class="form-control" rows="4" disabled>{{ old('data.description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">Сохранить</button>
                <a href="{{ route('admin.promocodes.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            function disableAllTypeBlocks() {
                document.querySelectorAll('.type-block').forEach(block => {
                    block.style.display = 'none';
                    block.querySelectorAll('input,select,textarea').forEach(el => el.disabled = true);
                });
            }

            function enableBlock(type) {
                if (!type) return;
                const block = document.getElementById('block-' + type);
                if (!block) return;
                block.style.display = 'block';
                block.querySelectorAll('input,select,textarea').forEach(el => el.disabled = false);
            }

            // Показ/скрытие по типу
            const promoType = document.getElementById('promoType');
            promoType.addEventListener('change', function() {
                disableAllTypeBlocks();
                enableBlock(this.value);
            });

            // Варианты тарифа (free_tariff)
            const tariffSelect = document.getElementById('tariffSelect');
            const variantSelect = document.getElementById('variantSelect');

            function fillVariants() {
                if (!tariffSelect) return;
                const opt = tariffSelect.options[tariffSelect.selectedIndex];
                const json = opt ? opt.getAttribute('data-variants') : null;
                variantSelect.innerHTML = '<option value="">Выберите вариант</option>';
                if (!json) return;

                try {
                    const data = JSON.parse(json); // [{ name, duration_days, ... }]
                    data.forEach(item => {
                        const o = document.createElement('option');
                        o.value = item.name;
                        o.textContent = `${item.name} (${item.duration_days} дней)`;
                        variantSelect.appendChild(o);
                    });

                    // Восстановить old значение (если было)
                    const selectedVariant = @json(old('data.variant_name'));
                    if (selectedVariant) {
                        [...variantSelect.options].forEach(opt => {
                            if (opt.value === selectedVariant) opt.selected = true;
                        });
                    }
                } catch (e) {
                    console.error('Bad variants JSON', e, json);
                }
            }

            tariffSelect?.addEventListener('change', fillVariants);

            // Инициализация при загрузке
            disableAllTypeBlocks();
            enableBlock(promoType.value);

            if (promoType.value === 'free_tariff') {
                fillVariants();
            }
        })();
    </script>
@endsection
