<div class="btn-group" role="group">
    <a href="{{ route('admin.sellers.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.index')) active @endif">Все продавцы</a>
    <a href="{{ route('admin.sellers.payments.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.payments.index')) active @endif">Платежные операции</a>
    <a href="{{ route('admin.sellers.products.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.products.index')) active @endif">Товары</a>
    <a href="{{ route('admin.sellers.ads.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.ads.index')) active @endif">Объявления</a>
    <a href="{{ route('admin.sellers.buybacks.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.buybacks.index')) active @endif">Выкупы</a>
</div>
