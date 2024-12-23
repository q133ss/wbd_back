<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Панель администратора')</title>
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="/bootstrap/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <style>
        .background-custom{
            background-color: #f5f6fa;
            min-height: 100vh;
        }
    </style>
</head>
<body>

<div class="row">
    <div class="col-md-2">
        @include('inc.sidebar')
    </div>
    <div class="col-md-10 background-custom">
        <h3 class="mt-2 mb-5">@yield('title')</h3>
        @yield('content')
    </div>
</div>
</div>
<style>
    .borr-30{
        border-radius: 30px;
    }
</style>
<script src="/jquery.min.js"></script>
@yield('scripts')
</body>
</html>
