@extends('layouts.tg')
@section('meta')
    <style>
        .continue-btn{
            background: #7F56D9;
            border-color: #7F56D9;
        }
    </style>
@endsection
@section('content')
    <strong>Завершение регистрации</strong><br/>
    <span>
        Почти готово, введите имя и задайте пароль для браузерной версии приложения wbdiscount.pro
    </span>
    <form action="{{route('tg.complete')}}" method="POST">
        @csrf
        <input type="text" class="form-control mt-2" placeholder="Телефон" value="{{$user->phone}}" name="phone">
        <input type="text" class="form-control mt-2" placeholder="Имя" value="{{$user->name}}" name="name">
        @if($user->role?->slug == 'seller')
        <input type="email" class="form-control mt-2" placeholder="Email" value="{{$user->email}}" name="email">
        @endif
        <input type="password" class="form-control mt-2" placeholder="Пароль" value="" required name="password">
        <input type="password" class="form-control mt-2" placeholder="Подтверждение пароля" value="" required name="password_confirmation">
        <button type="submit" class="btn btn-primary w-100 mt-2 continue-btn">Далее</button>
    </form>
@endsection
@section('scripts')
    <script>

    </script>
@endsection
