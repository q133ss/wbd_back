@extends('layouts.tg')
@section('meta')
    <style>
        .accept{
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .continue-btn{
            background: #7F56D9;
            border-color: #7F56D9;
        }

        .continue-btn:disabled{
            background: #7F56D9;
            border-color: #7F56D9;
        }
    </style>
@endsection
@section('content')
    <img src="/img/smile.png" alt="">
    <strong>Условия использования</strong><br/>
    <span>
        Продолжая использование, вы даете согласие на обработку персональных данных. Политика обработки персональных данных, а так же оферта были отправлены в телеграм бот {{ '@'.config('services.telegram.username') }}
    </span>
    <button onclick="goNext()" class="btn btn-primary w-100 mt-2 continue-btn" disabled="">Продолжить</button>
    <div class="accept mt-2">
        <input type="checkbox" id="accept">
        <label for="accept">
            <span style="font-weight: 500">Даю согласие</span>
            <br>
            На обработку персональных данных
        </label>
    </div>
@endsection
@section('scripts')
    <script>
        const checkbox = document.getElementById('accept');
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.btn.btn-primary');

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    button.removeAttribute('disabled'); // Убираем disabled, если чекбокс активен
                } else {
                    button.setAttribute('disabled', ''); // Добавляем disabled, если чекбокс неактивен
                }
            });
        });

        function goNext(){
            if(checkbox.checked){
                location.href='/telegram/login/get-contact/{{$role}}/{{$chat_id}}/{{$user_id}}';
            }
        }
    </script>
@endsection
