@extends('layouts.main')

@section('title', 'Продавцы')
@section('meta')
    <style>
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
        }
    </style>
@endsection
@section('content')
    <div class="card">
        <!-- Заголовок и кнопки -->
        <div class="card-header">
            <h5 class="mb-3">Продавцы</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.sellers.index') }}" class="btn btn-primary">Все продавцы</a>
                <button type="button" class="btn btn-outline-secondary">Платежные операции</button>
                <button type="button" class="btn btn-outline-secondary">Товары</button>
                <button type="button" class="btn btn-outline-secondary">Объявления</button>
                <button type="button" class="btn btn-outline-secondary">Выкупы</button>
            </div>
        </div>

        <!-- Поиск -->
        <div class="card-body">
            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по продавцам..." name="search">
                    <button class="btn btn-outline-primary" type="submit">Найти</button>
                </div>
            </form>

            <!-- Таблица продавцов -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата регистрации</th>
                        <th>Имя</th>
                        <th>ИНН</th>
                        <th>Юр. лицо</th>
                        <th>Магазин</th>
                        <th>Товары</th>
                        <th>Объявления</th>
                        <th>Активные</th>
                        <th>Выкупы</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sellers as $seller)
                        <tr>
                            <td>{{ $seller->id }}</td>
                            <td>{{ $seller->created_at->format('d.m.Y') }}</td>
                            <td>{{ $seller->name }}</td>
                            <td>{{ $seller->shop->inn ?? '-' }}</td>
                            <td>{{ $seller->shop->legal_name ?? '-' }}</td>
                            <td>{{ $seller->shop->wb_name ?? '-' }}</td>
                            <td>{{ $seller->products_count }}</td>
                            <td>{{ $seller->ads_count }}</td>
                            <td>{{ $seller->active_ads_count }}</td>
                            <td>{{ $seller->completed_buybacks_count }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $sellers->links() }}
            </div>
        </div>
    </div>
@endsection
