@extends('layouts.tg')
@section('meta')
    <style>
        .btn-purple {
            background-color: #8a4dff;
            color: white;
        }
        .btn-purple:hover {
            background-color: #7a3df0;
            color: white;
        }
        .tab-button {
            border: none;
            background: none;
            color: #6c63ff;
            font-weight: 500;
        }
        .tab-button.active {
            text-decoration: underline;
        }
        .filter-btn {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
        }
        .filter-btn.active {
            background-color: #f2ebff;
            color: #8a4dff;
        }
    </style>
@endsection
@section('content')
{{--    Модальное окно--}}
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Добавить товар</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" placeholder="Артикул WB">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-purple">Добавить</button>
                </div>
            </div>
        </div>
    </div>
{{--    Контент  --}}
    <div class="container py-4" style="max-width: 400px;">
        <!-- Верхняя кнопка -->
        <div class="d-flex justify-content-start mb-3">
            <button class="btn btn-purple rounded px-4 py-2" data-bs-toggle="modal" data-bs-target="#addProductModal">+ Добавить товар</button>
        </div>

        <!-- Навигация -->
        <div class="d-flex mb-2 border-bottom pb-2">
            <button class="tab-button active me-4">Товары</button>
            <button class="tab-button">Объявления</button>
        </div>

        <!-- Фильтры -->
        <div class="d-flex gap-2 mb-5">
            <a href="{{route('tg.dashboard')}}" class="btn filter-btn @if(!request()->has('status') && !request()->has('is_archived')) active @endif">Все</a>
            <a href="{{route('tg.dashboard', ['status' => '1'])}}" class="btn filter-btn @if(request()->status == '1') active @else btn-light @endif">Активные</a>
            <a href="{{route('tg.dashboard', ['status' => '0'])}}" class="btn filter-btn @if(request()->status == '0') active @else btn-light @endif">Неактивные</a>
            <a href="{{route('tg.dashboard', ['is_archived' => '1'])}}" class="btn filter-btn @if(request()->is_archived == 1) active @else btn-light @endif">Архив</a>
        </div>
        @if($products->isEmpty())
            <div class="text-center mt-5 pt-5">
                <a class="btn btn-purple rounded px-4 py-2 mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">+ Добавить товар</a>
                <p class="text-muted">Загрузите первый товар, чтобы<br>начать продвижение</p>
            </div>
        @endif
    </div>
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
