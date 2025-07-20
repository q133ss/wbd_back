<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerCategory;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        return Partner::with('category', 'img')->orderBy('created_at', 'desc')->paginate();
    }

    public function categories()
    {
        return PartnerCategory::get();
    }
}
