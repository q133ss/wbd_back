@extends('layouts.main')

@section('title', 'Роли')
@section('meta')
    <style>
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <!-- Заголовок и кнопки -->
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список ролей</h5>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">+ Добавить роль</a>
        </div>

        <!-- Поиск -->
        <div class="card-body">
            <!-- Таблица ролей -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Slug</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->slug }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning">Изменить</a>
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Удалить роль?')" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Ролей пока нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $roles->links('inc.pagination') }}
            </div>
        </div>
    </div>
@endsection
