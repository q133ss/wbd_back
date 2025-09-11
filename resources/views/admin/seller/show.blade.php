@extends('layouts.main')

@section('title', 'Детали продавца')

@section('meta')
    <style>
        .seller-info .row {
            margin-bottom: 1rem;
        }
        .seller-info .label {
            font-weight: bold;
            color: #555;
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-3">Информация о продавце</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.sellers.index') }}" class="btn btn-primary">Все продавцы</a>
            </div>
        </div>

        <div class="card-body seller-info">
            <!-- ID и ИНН -->
            <div class="row">
                <div class="col-md-6">
                    <div class="label">ID пользователя:</div>
                    <div>{{ $user->id }}</div>
                </div>
                <div class="col-md-6">
                    <div class="label">ИНН:</div>
                    <div>{{ $user->shop->inn ?? '-' }}</div>
                </div>
            </div>

            <!-- Две колонки с основной информацией -->
            <div class="row">
                <div class="col-md-6">
                    <div class="label">Наименование юр. лица:</div>
                    <div>{{ $user->shop->legal_name ?? '-' }}</div>

                    <div class="label mt-3">Название магазина:</div>
                    <div>{{ $user->shop->wb_name ?? '-' }}</div>

                    <div class="label mt-3">Имя пользователя:</div>
                    <div>{{ $user->name }}</div>

                    <div class="label mt-3">Номер телефона:</div>
                    <div>{{ $user->phone ?? '-' }}</div>

                    <div class="label mt-3">Email:</div>
                    <div>{{ $user->email }}</div>

                    <div class="label mt-3">UTM (Source):</div>
                    <div>{{ $user->utm_source ?? '-' }}</div>

                    <div class="label mt-3">UTM (Medium):</div>
                    <div>{{ $user->utm_medium ?? '-' }}</div>

                    <div class="label mt-3">UTM (Campaign):</div>
                    <div>{{ $user->utm_campaign ?? '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="label">Дата регистрации:</div>
                    <div>{{ $user->created_at->format('d.m.Y') }}</div>

                    <div class="label mt-3">Тариф:</div>
                    <div>{{ $user->tariffs()->first()->name ?? '-' }}</div>

                    <div class="label mt-3">Выкупов в процессе:</div>
                    <div>{{ $buybacksProccess ?? 0 }}</div>

                    <div class="label mt-3">Выкупов успешно:</div>
                    <div>{{ $buybackSuccess ?? 0 }}</div>

                    <div class="label mt-3">Пригласивший:</div>
                    @if($user->refer)
                        <a class="text-success" href="{{route('admin.sellers.show', $user->refer->id)}}">{{ $user->refer->name }}</a>
                    @else
                        <div>-</div>
                    @endif
                </div>
            </div>

            <form action="{{route('admin.sellers.update', $user->id)}}" method="POST">
                @csrf
                @method('PATCH')
                <label for="comment">Комментарий (видит только админ)</label>
                <textarea name="comment" class="form-control" id="comment" cols="30" rows="10">{{$user->comment}}</textarea>
                <button class="btn btn-primary mt-2" type="submit">Сохранить комментарий</button>
            </form>

            <!-- Кнопки снизу слева -->
            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('admin.loginAs', $user->id) }}" class="btn btn-warning">
                    Авторизоваться под пользователем
                </a>
                <form action="{{ route('admin.sellers.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Удалить пользователя?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить пользователя</button>
                </form>
            </div>
        </div>
    </div>
@endsection
