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
    <img src="/img/dialog.png" alt="">
    <strong>Поделитесь контактом</strong><br/>
    <span>
        Для продолжения регистрации в сервисе поделитесь номером телефона, привязанным к вашу аккаунту.
    </span>
    <button onclick="share()" class="btn btn-primary w-100 mt-2 continue-btn">Поделиться</button>
@endsection
@section('scripts')
    <script>
        window.Telegram.WebApp.ready();

        function share() {
            Telegram.WebApp.requestContact((success, info) => {
                if (!success || !info || info.status !== 'sent') {
                    return;
                }
                const contact = info.responseUnsafe.contact;
                const name = `${contact.first_name || ''} ${contact.last_name || ''}`.trim();
                location.href=`/telegram/login/complete/${contact.user_id}/${contact.phone_number}/{{$role}}/{{$chat_id}}/${contact.first_name}/${contact.last_name}`
            });
        }
    </script>
@endsection
