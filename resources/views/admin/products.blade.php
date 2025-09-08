@extends('layouts.main')

@section('title', 'Продукты')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Список продуктов</h5>
        </div>
        <div class="card-body">
            <form class="mb-4" method="GET" action="{{ route('admin.products.index') }}">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Поиск по имени, описанию, цене" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">Все статусы</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Активен</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Неактивен</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="is_archived" class="form-control">
                            <option value="">Все товары</option>
                            <option value="1" {{ request('is_archived') === '1' ? 'selected' : '' }}>Архивные</option>
                            <option value="0" {{ request('is_archived') === '0' ? 'selected' : '' }}>Не архивные</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Фильтровать</button>
                    </div>
                </div>
            </form>

            <div class="card-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Артикул</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Скидка</th>
                    <th>Рейтинг</th>
                    <th>Кол-во</th>
                    <th>Категория</th>
                    <th>Архив</th>
                    <th>Изображение</th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->wb_id }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->price }}</td>
                        <td>{{ $product->discount }}%</td>
                        <td>{{ $product->rating }}</td>
                        <td>{{ $product->quantity_available }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td>{{ $product->is_archived ? 'Да' : 'Нет' }}</td>
                        <td>
                            @if($product->images)
                                <img src="{{ $product->images[0] }}" alt="" width="50">
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $products->links('inc.pagination') }}
        </div>
    </div>
@endsection
