@extends('layouts.main')

@section('title', 'Детали покупателя')

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
            <h5 class="mb-3">Информация о покупателе</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.buyer.index') }}" class="btn btn-primary">Все покупатели</a>
            </div>
        </div>

        <div class="card-body seller-info">
            <!-- ID и ИНН -->
            <div class="row">
                <div class="col-md-6">
                    <div class="label">ID пользователя:</div>
                    <div>{{ $user->id }}</div>
                </div>
            </div>

            <!-- Две колонки с основной информацией -->
            <div class="row">
                <div class="col-md-6">
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

            <form action="{{route('admin.buyer.update', $user->id)}}" method="POST">
                @csrf
                @method('PATCH')
                <label for="comment">Комментарий (видит только админ)</label>
                <textarea name="comment" class="form-control" id="comment" cols="30" rows="10">{{$user->comment}}</textarea>
                <button class="btn btn-primary mt-2" type="submit">Сохранить</button>
            </form>

            <!-- Кнопки снизу слева -->
            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('admin.loginAs', $user->id) }}" class="btn btn-warning">
                    Авторизоваться под пользователем
                </a>
                <form action="{{ route('admin.buyer.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Удалить пользователя?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить пользователя</button>
                </form>
                @if($user->is_frozen)
                    <button class="btn btn-success" onclick="unfrozen()">Разморозить пользователя</button>
                    @else
                    <button class="btn btn-dark" onclick="frozen()">Заморозить пользователя</button>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function unfrozen(){
            if (confirm('Разморозить пользователя?')) {
                location.href = '{{ route('admin.user.unfrozen', $user->id) }}'
            }
        }
        function frozen(){
            if (confirm('Заморозить пользователя?')) {
                location.href = '{{ route('admin.user.frozen', $user->id) }}'
            }
        }
    </script>
@endsection
