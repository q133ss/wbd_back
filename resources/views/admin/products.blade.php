@extends('layouts.main')

@section('title', 'Продукты')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Список продуктов</h5>
        </div>
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

            {{ $products->links() }}
        </div>
    </div>
@endsection
