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
                    <input id="wb-article" type="text" class="form-control" placeholder="Артикул WB">
                    <div class="text-danger mt-2 d-none" id="fetch-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-purple" id="fetch-btn">Добавить</button>
                </div>
            </div>
        </div>
    </div>

{{-- Добавление магазина --}}
<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-4">
            <h5 class="mb-3">Добавление магазина</h5>
            <div class="mb-3 text-muted" id="shop-info-text"></div>
            <input type="text" class="form-control mb-2" id="product-title" readonly>
            <input type="text" class="form-control mb-2" id="product-price" readonly>
            <input type="text" class="form-control mb-2" id="product-brand" readonly>
            <img id="product-image" class="img-fluid mt-3" style="max-height: 300px;">
            <div class="d-flex justify-content-end gap-3 mt-4">
                <button class="btn btn-purple" id="final-submit">Далее</button>
                <button class="btn btn-link text-muted" data-bs-dismiss="modal">Отмена</button>
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
        @if($products->isEmpty() && $user->shop == null)
            <div class="text-center mt-5 pt-5">
                <a class="btn btn-purple rounded px-4 py-2 mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">+ Добавить товар</a>
                <p class="text-muted">Загрузите первый товар, чтобы<br>начать продвижение</p>
            </div>
        @elseif($products->isEmpty())
            <div class="text-center mt-5 pt-5">
                <p class="text-muted">Товары не найдены</p>
            </div>
        @elseif(!$products->isEmpty())
            @foreach($products as $product)
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-body position-relative">
                    <!-- Верхняя часть: изображение + название + переключатель -->
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex">
{{--                            @dd($product)--}}
                            <img src="{{$product->images[0]}}" alt="Product" class="rounded-3 me-3" width="60" height="60">
                            <div>
                                <h6 class="mb-1">{{Str::limit($product->name, 24, '..')}}</h6>
                                <small class="text-muted">{{$product->wb_id}}</small>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="switch1" @if($product->status) checked @endif>
                        </div>
                    </div>

                    <!-- Первый ряд -->
                    <div class="row text-center mt-4">
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">ОБЪЯВЛЕНИЯ</small>
                            <strong>{{$product->ads_count}} шт.</strong>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">ПРОСМОТРЫ</small>
                            <strong>{{$product->ads_sum_views_count ?? 0}}</strong>
                        </div>

                        <div class="col-6 mb-1">
                            <small class="text-muted d-block">ВЫКУПЫ В ПРОЦЕССЕ</small>
                            <strong>{{$product->ads->sum('proccess_buybacks_count')}}</strong>
                        </div>
                        <div class="col-6 mb-1">
                            <small class="text-muted d-block">ЗАВЕРШЕННЫЕ ВЫКУПЫ</small>
                            <strong>{{$product->ads->sum('completed_buybacks_count')}}</strong>
                        </div>
                    </div>

                    <!-- Кнопка-меню -->
                    <div class="position-absolute bottom-0 end-0 p-3">
                        <button class="btn btn-link p-0 text-dark">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('fetch-btn').addEventListener('click', async () => {
            const article = document.getElementById('wb-article').value.trim();
            const errorDiv = document.getElementById('fetch-error');
            errorDiv.classList.add('d-none');

            if (!article) {
                errorDiv.textContent = "Введите артикул.";
                errorDiv.classList.remove('d-none');
                return;
            }

            try {
                const response = await fetch(`/api/wb/fetch-product/${article}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer {{$token}}'
                    }
                });
                if (response.status === 404) {
                    errorDiv.textContent = "Товар не найден.";
                    errorDiv.classList.remove('d-none');
                } else if (response.status === 403) {
                    errorDiv.textContent = "Данный товар принадлежит другому продавцу.";
                    errorDiv.classList.remove('d-none');
                } else if (response.ok) {
                    const data = await response.json();
                    const { product, shop } = data;

                    // Заполнение данных во второе модальное окно
                    document.getElementById('product-title').value = product.name;
                    document.getElementById('product-price').value = product.price;
                    document.getElementById('product-brand').value = product.brand;
                    document.getElementById('product-image').src = product.images[0] || '';
                    document.getElementById('shop-info-text').textContent =
                        `Этот товар находится в магазине продавца "${shop.wb_name}". Подтвердите добавление магазина в ваш профиль на Wbdiscount.`;

                    // Показ второго модального окна
                    const modal1 = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal1.hide();

                    const modal2 = new bootstrap.Modal(document.getElementById('infoModal'));
                    modal2.show();
                } else {
                    errorDiv.textContent = "Произошла ошибка. Попробуйте позже.";
                    errorDiv.classList.remove('d-none');
                }
            } catch (err) {
                errorDiv.textContent = "Ошибка сети.";
                errorDiv.classList.remove('d-none');
            }
        });

        // Добавление товара
        document.getElementById('final-submit').addEventListener('click', async () => {
            const article = document.getElementById('wb-article').value.trim();
            const url = `{{ url('/api/wb/add-product/') }}/${article}`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Authorization': 'Bearer {{$token}}'
                    },
                    body: JSON.stringify({}) // если нужны данные
                });

                if (response.ok) {
                    const data = await response.json();
                    const productId = data.product?.id;

                    @if(!$products->isEmpty())
                    // Товаров ещё нет — редиректим на создание объявления
                    window.location.href = "{{ route('tg.ads.create', ':id') }}".replace(':id', productId);
                    @else
                    // Товары уже есть — просто перезагрузим
                    window.location.reload();
                    @endif
                } else {
                    alert("Ошибка при добавлении товара.");
                }
            } catch (err) {
                console.error(err);
                alert("Ошибка сети.");
            }
        });
    </script>
@endsection
