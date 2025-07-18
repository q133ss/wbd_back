<?php

namespace App\Http\Controllers\TgApp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = User::where('telegram_id', $request->uid)->first();
        if(!$user){
            abort(403, 'Вы не авторизованны');
        }
        $products = Product::where('shop_id', $user->shop?->id)->withFilter($request)->get();
        return view('app.dashboard.index', compact('user', 'products'));
    }
}
