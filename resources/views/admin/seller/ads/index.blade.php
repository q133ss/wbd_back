@extends('layouts.main')

@section('title', 'Список объявлений')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-3">Объявления</h5>
            @include('admin.seller.navbar')
        </div>

        <div class="card-body">
            <form class="mb-4" method="GET" action="{{ route('admin.sellers.ads.index') }}">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по ID, артикулу или ID продавца..." name="search" value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit">Найти</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>ID товара</th>
                        <th>Артикул товара</th>
                        <th>ID продавца</th>
                        <th>Статус</th>
                        <th>Кол-во выкупов</th>
                        <th>Цена товара</th>
                        <th>Кэшбек %</th>
                        <th>Кэшбек ₽</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($ads as $ad)
                        <tr>
                            <td>{{ $ad->id }}</td>
                            <td>{{ $ad->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $ad->product_id }}</td>
                            <td>{{ $ad->product->wb_id ?? '-' }}</td>
                            <td>{{ $ad->product->shop_id ?? '-' }}</td>
                            <td>
                                @if($ad->status)
                                    <span class="badge bg-success">Активно</span>
                                @else
                                    <span class="badge bg-secondary">Неактивно</span>
                                @endif
                            </td>
                            <td>{{ $ad->buybacks_count }}</td>
                            <td>{{ $ad->price_with_cashback }}</td>
                            <td>{{ $ad->cashback_percentage }}%</td>
                            <td>{{ number_format($ad->price_with_cashback * $ad->cashback_percentage / 100, 2, '.', '') }}</td>
                            <td>
                                <form action="{{ route('admin.sellers.ads.destroy', $ad->id) }}" method="POST" onsubmit="return confirm('Удалить объявление?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center">Объявлений нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $ads->links('inc.pagination') }}
            </div>
        </div>
    </div>
@endsection
