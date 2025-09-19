@extends('layouts.main')

@section('title', 'Автопостинг')

@section('content')
    <div class="row">
        <div class="col-xl-5 col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Настройки автопостинга</h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.autoposting.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" {{ old('is_enabled', $settings->is_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_enabled">Включить автопостинг</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Что публиковать</label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_photo" name="show_photo" value="1" {{ old('show_photo', $settings->show_photo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_photo">Фото товара</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_price" name="show_price" value="1" {{ old('show_price', $settings->show_price) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_price">Цена</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_cashback" name="show_cashback" value="1" {{ old('show_cashback', $settings->show_cashback) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_cashback">Кэшбек</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_conditions" name="show_conditions" value="1" {{ old('show_conditions', $settings->show_conditions) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_conditions">Условия</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="show_link" name="show_link" value="1" {{ old('show_link', $settings->show_link) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_link">Кнопка «Перейти к товару»</label>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7 col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Логи публикаций</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th class="text-nowrap">Дата</th>
                                <th>Объявление</th>
                                <th>Статус</th>
                                <th>Сообщение</th>
                                <th>Ошибка</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php use Illuminate\Support\Str; @endphp
                            @forelse($logs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                    <td>
                                        @if($log->ad)
                                            {{ $log->ad->name }}
                                        @else
                                            <span class="text-muted">Объявление #{{ $log->ad_id }} удалено</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->is_success)
                                            <span class="badge bg-success">Успех</span>
                                        @else
                                            <span class="badge bg-danger">Ошибка</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit(strip_tags($log->message ?? ''), 80) }}</td>
                                    <td>
                                        @if($log->error_message)
                                            <span class="text-danger">{{ Str::limit($log->error_message, 80) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">Логи отсутствуют</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3">
                        {{ $logs->links('inc.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
