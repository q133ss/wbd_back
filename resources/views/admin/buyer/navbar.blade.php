<div class="btn-group" role="group">
    <a href="{{ route('admin.buyer.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.index')) active @endif">Все покупатели</a>
    <a href="{{ route('admin.buyer.payments.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.payments.index')) active @endif">Платежные операции</a>
    <a href="{{ route('admin.buyer.buybacks.index') }}" class="btn btn-outline-dark @if(request()->routeIs('admin.sellers.buybacks.index')) active @endif">Выкупы</a>
</div>
