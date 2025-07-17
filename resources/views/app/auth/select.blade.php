@extends('layouts.tg')
@section('meta')
    <style>
        body {
            background-color: #ffffff;
            font-family: Arial, sans-serif;
        }
        .role-card {
            border-radius: 16px;
            background-color: #7749f8;
            color: #fff;
            padding: 30px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background-color 0.3s;
            max-height: 160px;
        }
        .role-card:hover {
            background-color: #693be0;
        }
        .role-card img {
            max-width: 180px;
        }
        .support {
            font-size: 14px;
            color: #000;
            text-align: center;
            margin-top: 40px;
        }
        .support a {
            color: #7749f8;
            text-decoration: none;
        }
        .support a:hover {
            text-decoration: underline;
        }
    </style>
@endsection
@section('content')
    <div class="text-center mb-4">
        <h5>Выберите роль:</h5>
    </div>
    <div class="role-card" id="buyer-card">
        <div><strong>Я покупатель</strong></div>
        <img src="/img/cart.png" alt="Покупатель" />
    </div>

    <div class="role-card" id="seller-card">
        <div><strong>Я продавец</strong></div>
        <img src="/img/pc.png" alt="Продавец" />
    </div>
@endsection
@section('scripts')
    <script>
        const tg = window.Telegram.WebApp;
        const uid = tg.initDataUnsafe.user.id;

        document.getElementById('buyer-card').onclick = () => {
            window.location.href = `/telegram/login/conditions/buyer/${uid}/{{$chatId}}`;
        };

        document.getElementById('seller-card').onclick = () => {
            window.location.href = `/telegram/login/conditions/seller/${uid}/{{$chatId}}`;
        };
    </script>
@endsection
