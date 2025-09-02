@extends('layouts.main')

@section('title', 'Настройки')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Настройки</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
                <small>
                    Используйте <code>&lt;br&gt;</code> чтобы сделать перенос строки в тексте.
                </small>
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="form-label">Инструкция покупателю</label>
                    <textarea name="review_cashback_instructions" class="form-control" rows="6">{{ old('review_cashback_instructions', $settings['review_cashback_instructions ']->value ?? '') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Сообщение после отправки скрина</label>
                    <textarea name="cashback_review_message" class="form-control" rows="4">{{ old('cashback_review_message', $settings['cashback_review_message']->value ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success">Сохранить настройки</button>
            </form>
        </div>
    </div>
@endsection
