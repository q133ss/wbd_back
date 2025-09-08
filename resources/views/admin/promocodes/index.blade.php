@extends('layouts.main')

@section('title', 'Промокоды')

@section('meta')
    <style>
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <!-- Заголовок -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список промокодов</h5>
            <a href="{{ route('admin.promocodes.create') }}" class="btn btn-primary btn-sm">+ Добавить промокод</a>
        </div>

        <div class="card-body">
            <!-- Поиск -->
            <form class="mb-4" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по названию или коду..." name="search" value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit">Найти</button>
                </div>
            </form>

            <!-- Таблица -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Код</th>
                        <th>Дата начала</th>
                        <th>Дата окончания</th>
                        <th>Макс. использование</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($promocodes as $promocode)
                        <tr>
                            <td>{{ $promocode->id }}</td>
                            <td>{{ $promocode->name }}</td>
                            <td>{{ $promocode->promocode }}</td>
                            <td>{{ \Carbon\Carbon::parse($promocode->start_date)?->format('d.m.Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($promocode->end_date)?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $promocode->max_usage ?? '∞' }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.promocodes.edit', $promocode) }}" class="btn btn-sm btn-warning">Изменить</a>
                                    <form action="{{ route('admin.promocodes.destroy', $promocode) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Удалить промокод?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Промокодов пока нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $promocodes->links('inc.pagination') }}
            </div>
        </div>
    </div>
@endsection
