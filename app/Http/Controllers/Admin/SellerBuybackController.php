<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use Illuminate\Http\Request;

class SellerBuybackController extends Controller
{
    public function index(Request $request)
    {
        $buybacks = Buyback::withFilter($request)->paginate();

        return view('admin.seller.buybacks.index', compact('buybacks'));
    }

}
