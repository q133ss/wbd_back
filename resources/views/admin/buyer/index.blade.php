@extends('layouts.main')

@section('title', 'Покупатели')
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
            <h5 class="mb-3">Покупатели</h5>
            @include('admin.buyer.navbar')
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
                        <th>Телефон</th>
                        <th>Выкупы</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($buyers as $buyer)
                        <tr onclick="location.href='{{ route('admin.buyer.show', $buyer->id) }}'" style="cursor: pointer;">
                            <td>{{ $buyer->id }}</td>
                            <td>{{ $buyer->created_at->format('d.m.Y') }}</td>
                            <td>{{ $buyer->name }}</td>
                            <td>{{ $buyer->phone }}</td>
                            <td>{{ $buyer->buybacks()->count() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $buyers->links('inc.pagination') }}
            </div>
        </div>
    </div>
@endsection
