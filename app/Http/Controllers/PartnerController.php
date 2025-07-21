<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerCategory;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        return Partner::with('category', 'img')->withFilter($request)->orderBy('created_at', 'desc')->paginate();
    }

    public function categories()
    {
        return PartnerCategory::get();
    }
}
