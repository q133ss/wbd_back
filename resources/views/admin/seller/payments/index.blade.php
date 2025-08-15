@extends('layouts.main')

@section('title', 'Платежные операции')

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
            <h5 class="mb-3">Платежные операции</h5>
            @include('admin.seller.navbar')
        </div>

        <!-- Поиск -->
        <div class="card-body">
            <form class="mb-4" method="GET" action="{{ route('admin.sellers.payments.index') }}">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Поиск по ID, пользователю..." name="search" value="{{ request('search') }}">
                    <button class="btn btn-outline-primary" type="submit">Найти</button>
                </div>
            </form>

            <!-- Таблица -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID транзакции</th>
                        <th>Сумма</th>
                        <th>Тип</th>
                        <th>Дата и время</th>
                        <th>Описание</th>
                        <th>ID пользователя</th>
                        <th>ID объявления</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->id }}</td>
                            <td>{{ number_format($transaction->amount, 2, '.', ' ') }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>{{ $transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '-' }}</td>
                            <td>{{ $transaction->description ?? '-' }}</td>
                            <td>{{ $transaction->user_id }}</td>
                            <td>{{ $transaction->ads_id ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Нет транзакций</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
@endsection
