@extends('layouts.main')

@section('title', 'Детали выкупа #'.$buyback->id)

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Детали выкупа #{{ $buyback->id }}</h5>
            <a href="{{ route('admin.buybacks.index') }}" class="btn btn-secondary float-end">Назад</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>ID</th>
                    <td>{{ $buyback->id }}</td>
                </tr>
                <tr>
                    <th>Объявление</th>
                    <td>{{ $buyback->ad?->name ?? '—' }} | ID: {{$buyback->ad?->id}}</td>
                </tr>
                <tr>
                    <th>Пользователь</th>
                    <td>
                        @if($buyback->user)
                            @php
                                $roleSlug = $buyback->user->role?->slug;
                                $userRoute = $roleSlug === 'seller'
                                    ? route('admin.seller.edit', $buyback->user->id)
                                    : route('admin.buyer.show', $buyback->user->id);
                            @endphp
                            <a href="{{ $userRoute }}">{{ $buyback->user->name }}</a> ({{ $roleSlug }})
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Статус</th>
                    <td>{{ $buyback->status ? 'Активен' : 'Неактивен' }}</td>
                </tr>
                <tr>
                    <th>Цена товара</th>
                    <td>{{ $buyback->product_price }}</td>
                </tr>
                <tr>
                    <th>Цена с кэшбеком</th>
                    <td>{{ $buyback->price_with_cashback }}</td>
                </tr>
                <tr>
                    <th>Кэшбек (%)</th>
                    <td>{{ $buyback->cashback_percentage }}%</td>
                </tr>
                <tr>
                    <th>Архив</th>
                    <td>{{ $buyback->is_archived ? 'Да' : 'Нет' }}</td>
                </tr>
                <tr>
                    <th>Отзывы</th>
                    <td>
                        Продавец: {{ $buyback->has_review_by_seller ? 'Есть' : 'Нет' }} <br>
                        Покупатель: {{ $buyback->has_review_by_buyer ? 'Есть' : 'Нет' }}
                    </td>
                </tr>
                <tr>
                    <th>Фото</th>
                    <td>
                        Заказ: {{ $buyback->is_order_photo_sent ? 'Отправлено' : 'Нет' }} <br>
                        Отзыв: {{ $buyback->is_review_photo_sent ? 'Отправлено' : 'Нет' }} <br>
                        Оплата: {{ $buyback->is_payment_photo_sent ? 'Отправлено' : 'Нет' }}
                    </td>
                </tr>
                <tr>
                    <th>Ключевое слово</th>
                    <td>{{ $buyback->keyword ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Создано</th>
                    <td>{{ $buyback->created_at->format('d.m.Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Обновлено</th>
                    <td>{{ $buyback->updated_at->format('d.m.Y H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>
@endsection
