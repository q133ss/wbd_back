<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use Illuminate\Http\Request;

class SellerBuybackController extends Controller
{
    public function index()
    {
        $buybacks = Buyback::with('ad', 'ad.product')->paginate();
        return view('admin.seller.buybacks.index', compact('buybacks'));
    }
}
