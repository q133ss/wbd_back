<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::call(function () {
    app(\App\Http\Controllers\SitemapController::class)->generate();
    Artisan::call('verification:clear');
})->daily();
