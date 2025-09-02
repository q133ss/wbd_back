@extends('layouts.main')

@section('title', 'Выкупы')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Список выкупов</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Объявление</th>
                    <th>Пользователь</th>
                    <th>Статус</th>
                    <th>Цена товара</th>
                    <th>Цена с кэшбеком</th>
                    <th>Кэшбек (%)</th>
                    <th>Архив</th>
                    <th>Создано</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                @foreach($buybacks as $buyback)
                    <tr>
                        <td>{{ $buyback->id }}</td>
                        <td>{{ $buyback->ad?->name ?? '—' }} | ID: {{$buyback->ad?->id}}</td>
                        <td>
                            @if($buyback->user)
                                @php
                                    $roleSlug = $buyback->user->role?->slug;
                                    $userRoute = $roleSlug === 'seller'
                                        ? route('admin.seller.edit', $buyback->user->id)
                                        : route('admin.buyer.show', $buyback->user->id);
                                @endphp
                                <a href="{{ $userRoute }}">{{ $buyback->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $buyback->status ? 'Активен' : 'Неактивен' }}</td>
                        <td>{{ $buyback->product_price }}</td>
                        <td>{{ $buyback->price_with_cashback }}</td>
                        <td>{{ $buyback->cashback_percentage }}%</td>
                        <td>{{ $buyback->is_archived ? 'Да' : 'Нет' }}</td>
                        <td>{{ $buyback->created_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.buybacks.show', $buyback) }}" class="btn btn-sm btn-primary">Просмотр</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $buybacks->links() }}
            </div>
        </div>
    </div>
@endsection
