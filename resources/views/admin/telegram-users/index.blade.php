@extends('layouts.main')

@section('title', 'Пользователи TG')

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Пользователи Telegram</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.telegram-users.refresh') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Обновить</button>
                        </form>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.telegram-users.all') }}">Все участники</a>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.telegram-users.new') }}">Новые участники</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
