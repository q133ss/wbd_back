<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class SellerAdController extends Controller
{
    public function index()
    {
        $ads = Ad::with(['product' => function($query) {
            $query->withCount('buybacks');
        }])->withCount('buybacks')->paginate();
        return view('admin.seller.ads.index', compact('ads'));
    }

    public function delete(string $id)
    {
        Ad::findOrFail($id)->delete();
        return back();
    }
}
