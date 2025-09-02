@extends('layouts.main')

@section('title', 'Редактировать промокод')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Редактировать промокод</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.promocodes.update', $promocode) }}" method="POST" id="promoForm">
                @csrf
                @method('PUT')

                @php
                    // Если в модели есть $casts['data'=>'array'], то $promocode->data — массив
                    $d = old('data', $promocode->data ?? []);
                @endphp

                    <!-- Общие поля -->
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $promocode->name) }}" required>
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Код</label>
                    <input type="text" name="promocode" class="form-control" value="{{ old('promocode', $promocode->promocode) }}" required>
                    @error('promocode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата начала</label>
                        <input type="date" name="start_date" class="form-control"
                               value="{{ old('start_date', $promocode->start_date) }}" required>
                        @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Дата окончания</label>
                        <input type="date" name="end_date" class="form-control"
                               value="{{ old('end_date', $promocode->end_date) }}">
                        @error('end_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Сколько раз один юзер может использовать промокод?</label>
                    <input type="number" name="max_usage" class="form-control" value="{{ old('max_usage', $promocode->max_usage) }}">
                    @error('max_usage') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                @php
                    // Получаем данные data как массив (безопасно)
                    $raw = old('data', $promocode->data ?? []);
                    if (is_string($raw)) {
                        $d = json_decode($raw, true) ?: [];
                    } elseif (is_array($raw)) {
                        $d = $raw;
                    } else {
                        $d = (array) $raw;
                    }

                    $type = data_get($d, 'type');
                    $preselectedTariff = data_get($d, 'tariff_name');
                    $preselectedVariant = data_get($d, 'variant_name');
                @endphp

                <!-- Тип промокода -->
                <div class="mb-3">
                    <label class="form-label">Тип промокода</label>
                    <select name="data[type]" id="promoType" class="form-control" required>
                        <option value="">Выберите тип</option>
                        <option value="custom_tariff" {{ $type === 'custom_tariff' ? 'selected' : '' }}>Custom Tariff</option>
                        <option value="discount" {{ $type === 'discount' ? 'selected' : '' }}>Discount</option>
                        <option value="extra_days" {{ $type === 'extra_days' ? 'selected' : '' }}>Extra Days</option>
                        <option value="free_tariff" {{ $type === 'free_tariff' ? 'selected' : '' }}>Free Tariff</option>
                    </select>
                </div>

                <!-- Discount -->
                <div class="mb-3 type-block" id="block-discount" style="display:none;">
                    <label class="form-label">Тариф</label>
                    <select name="data[tariff_name]" class="form-control" disabled>
                        @foreach($tariffs as $tariff)
                            <option value="{{ $tariff->name }}"
                                {{ data_get($d,'tariff_name')===$tariff->name?'selected':'' }}>
                                {{ $tariff->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Процент скидки</label>
                    <input type="number" name="data[discount_percent]" class="form-control" min="1" max="100"
                           value="{{ data_get($d,'discount_percent') }}" disabled>
                </div>

                <!-- Extra Days -->
                <div class="mb-3 type-block" id="block-extra_days" style="display:none;">
                    <label class="form-label">Тариф</label>
                    <select name="data[tariff_name]" class="form-control" disabled>
                        @foreach($tariffs as $tariff)
                            <option value="{{ $tariff->name }}"
                                {{ data_get($d,'tariff_name')===$tariff->name?'selected':'' }}>
                                {{ $tariff->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Количество дней</label>
                    <input type="number" name="data[extra_days]" class="form-control" min="1"
                           value="{{ data_get($d,'extra_days') }}" disabled>
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
                            >{{ $tariff->name }}</option>
                        @endforeach
                    </select>

                    <label class="form-label mt-2">Вариант тарифа</label>
                    <select name="data[variant_name]" id="variantSelect" class="form-control" disabled>
                        <option value="">Сначала выберите тариф</option>
                    </select>
                </div>

                <!-- Custom Tariff -->
                <div class="mb-3 type-block" id="block-custom_tariff" style="display:none;">
                    <label class="form-label">Еще не готово</label>
                    <textarea name="data[description]" class="form-control" rows="4" disabled>{{ data_get($d,'description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">Обновить</button>
                <a href="{{ route('admin.promocodes.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const promoType = document.getElementById('promoType');
            const tariffSelect = document.getElementById('tariffSelect');
            const variantSelect = document.getElementById('variantSelect');

            // Значения, переданные с сервера
            const preType = @json($type);
            const preTariff = @json($preselectedTariff);
            const preVariant = @json($preselectedVariant);

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

            function fillVariants(keepSelected = true) {
                if (!tariffSelect || !variantSelect) return;
                const opt = tariffSelect.options[tariffSelect.selectedIndex];
                const json = opt ? opt.getAttribute('data-variants') : null;
                variantSelect.innerHTML = '<option value="">Выберите вариант</option>';
                if (!json) return;

                try {
                    const arr = JSON.parse(json);
                    arr.forEach(item => {
                        const o = document.createElement('option');
                        o.value = item.name;
                        o.textContent = `${item.name} (${item.duration_days} дней)`;
                        variantSelect.appendChild(o);
                    });

                    if (keepSelected && preVariant) {
                        [...variantSelect.options].forEach(o => {
                            if (o.value === preVariant) o.selected = true;
                        });
                    }
                } catch (e) {
                    console.error('Bad variants JSON', e);
                }
            }

            // Слушатели
            promoType.addEventListener('change', function () {
                disableAllTypeBlocks();
                enableBlock(this.value);
                if (this.value === 'free_tariff') {
                    // если тариф уже выбран (preTariff), выставим его
                    if (preTariff) {
                        [...tariffSelect.options].forEach(o => {
                            o.selected = (o.value === preTariff);
                        });
                    }
                    fillVariants(true);
                }
            });

            tariffSelect?.addEventListener('change', function () {
                fillVariants(false);
            });

            // Инициализация: выставим значение типа (если есть) и откроем блок
            if (preType) {
                promoType.value = preType;
            }

            // Отключаем все блоки, затем включаем нужный
            disableAllTypeBlocks();
            enableBlock(promoType.value);

            // Для free_tariff: выставляем тариф + варианты
            if (promoType.value === 'free_tariff') {
                if (preTariff) {
                    [...tariffSelect.options].forEach(o => {
                        if (o.value === preTariff) o.selected = true;
                    });
                }
                fillVariants(true);
            }
        });
    </script>
@endsection

