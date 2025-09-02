@extends('layouts.main')

@section('title', 'Добавить роль')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Добавить роль</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required>
                </div>

                <button type="submit" class="btn btn-success">Сохранить</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
