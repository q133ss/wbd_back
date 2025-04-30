@extends('layouts.main')
@section('title', 'Продавцы')
@section('content')
    @include('admin.seller.navbar')
    <input type="text" placeholder="Поиск" class="form-control w-25">
    <div style="overflow-x: scroll">
    <table class="table">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Дата регистрации</th>
            <th scope="col">Баланс</th>
            <th scope="col">Баланс выкупов</th>
            <th scope="col">Имя</th>
            <th scope="col">ИНН</th>
            <th scope="col">Наименование юр лица</th>
            <th scope="col">Название магазина</th>
            <th scope="col">Товаров</th>
            <th scope="col">Объявлений</th>
            <th scope="col">Активные объявления</th>
            <th scope="col">Выкупов завершено</th>
            <th scope="col">UTM Medium</th>
            <th scope="col">UTM source</th>
            <th scope="col">UTM campaign</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($sellers as $seller)
        <tr>
            <th scope="row">{{$seller->id}}</th>
            <td>{{$seller->created_at->format('d-m-Y H:i')}}</td>
            <td>{{$seller->balance}}₽</td>
            <td>{{$seller->baybacks}}</td>
            <td>{{$seller->name}}</td>
            <td>{{$seller->shop?->inn}}</td>
            <td>{{$seller->shop?->wb_name}}</td>
            <td>{{$seller->shop?->legal_name}}</td>
            <td>{{$seller->shop?->products?->count()}}</td>
            <td>{{$seller->shop?->ads?->count()}}</td>
            <td>{{$seller->shop?->ads?->where('status', 'true')->count()}}</td>
            <td>{{$seller->shop?->ads?->buybacks?->where('status', 'completed')->count()}}</td>
            <td>2</td>
            <td>2</td>
            <td>2</td>
            <td>2</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
@endsection
