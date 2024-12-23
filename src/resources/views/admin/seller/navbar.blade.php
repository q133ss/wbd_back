<div class="d-flex" style="gap:10px; border-bottom: 1px solid #eeeeee">
    <a href="{{route('seller.index')}}" class="nav-active">Все продавцы</a>
    <a href="{{route('seller.index')}}">Платежные операции</a>
    <a href="{{route('seller.index')}}">Товары</a>
    <a href="{{route('seller.index')}}">Объявления</a>
    <a href="{{route('seller.index')}}">Выкупы</a>
</div>

<style>
    .nav-active{
        border-bottom: 3px solid var(--primary);
        font-weight: bold;
    }
</style>
