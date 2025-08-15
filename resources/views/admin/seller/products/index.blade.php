@extends('layouts.main')

@section('title', 'Список товаров')

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
            <h5 class="mb-3">Товары продавца</h5>
            @include('admin.seller.navbar')
        </div>

        <!-- Поиск -->
        <div class="card-body">
            <form class="mb-4" method="GET" action="{{ route('admin.sellers.products.index') }}">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по ID товара или артикулу..." name="search" value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit">Найти</button>
                </div>
            </form>

            <!-- Таблица -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID товара</th>
                        <th>Дата создания</th>
                        <th>Кол-во объявлений</th>
                        <th>Статус</th>
                        <th>Кол-во выкупов</th>
                        <th>Артикул</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>{{ $product->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $product->ads_count }}</td>
                            <td>
                                @if($product->status)
                                    <span class="badge bg-success">Активен</span>
                                @else
                                    <span class="badge bg-secondary">Неактивен</span>
                                @endif
                            </td>
                            <td>{{ $product->buybacks_count }}</td>
                            <td>{{ $product->wb_id }}</td>
                            <td>
                                <form action="{{ route('admin.sellers.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Удалить товар?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Товаров нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
