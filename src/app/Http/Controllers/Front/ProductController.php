<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $adsQuery = Ad::withFilter($request)->withSorting($request);
        $ads = $adsQuery->paginate(18);
        return response()->json($ads);
    }
}
