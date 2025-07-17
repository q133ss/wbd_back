@extends('layouts.tg')
@section('meta')
@endsection
@section('content')
    Name: {{auth('sanctum')->user()}}
@endsection
