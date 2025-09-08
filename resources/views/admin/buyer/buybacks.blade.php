@extends('layouts.main')

@section('title', 'Список выкупов')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-3">Выкупы</h5>
            @include('admin.buyer.navbar')
        </div>

        <div class="card-body">
            <form class="mb-4" method="GET" action="{{ route('admin.buyer.buybacks.index') }}">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по ID, артикулу, ID объявления, продавца или покупателя..." name="search" value="{{ request('search') }}">
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
                        <th>ID объявления</th>
                        <th>ID продавца</th>
                        <th>ID покупателя</th>
                        <th>Статус</th>
                        <th>Сумма</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($buybacks as $buyback)
                        <tr>
                            <td>{{ $buyback->id }}</td>
                            <td>{{ optional($buyback->created_at)->format('d.m.Y H:i') ?? '-' }}</td>
                            <td>{{ $buyback->ad->product_id ?? '-' }}</td>
                            <td>{{ $buyback->ad->product->wb_id ?? '-' }}</td>
                            <td>{{ $buyback->ads_id }}</td>
                            <td>{{ $buyback->ad->user_id ?? '-' }}</td>
                            <td>{{ $buyback->user_id }}</td>
                            <td>
                                @if($buyback->status === 'pending')
                                    <span class="badge bg-warning">В ожидании</span>
                                @elseif($buyback->status === 'approved')
                                    <span class="badge bg-success">Подтверждено</span>
                                @elseif($buyback->status === 'rejected')
                                    <span class="badge bg-danger">Отклонено</span>
                                @else
                                    <span class="badge bg-secondary">{{ $buyback->status }}</span>
                                @endif
                            </td>
                            <td>{{ $buyback->price_with_cashback }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Выкупов нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $buybacks->links('inc.pagination') }}
            </div>
        </div>
    </div>
@endsection
