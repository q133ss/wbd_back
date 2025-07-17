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
        function share() {
            const tg = window.Telegram.WebApp;

            // Если WebApp поддерживает запрос контакта
            if (tg.platform !== 'unknown') {
                tg.showPopup({
                    title: 'Поделиться контактом',
                    message: 'Разрешить доступ к вашему номеру телефона?',
                    buttons: [
                        {
                            type: 'default',
                            text: 'Отмена',
                        },
                        {
                            type: 'ok',
                            text: 'Разрешить',
                            request_contact: true, // Запрашиваем контакт
                        }
                    ]
                }, (buttonId) => {
                    if (buttonId === 'ok') {
                        tg.sendData(JSON.stringify({
                            action: 'share_contact',
                            phone: tg.initDataUnsafe.user?.phone_number,
                        }));
                    }
                });
            } else {
                // Альтернативный вариант (если WebApp не поддерживает контакты)
                alert('Пожалуйста, поделитесь номером вручную.');
            }
        }
    </script>
@endsection
