<?php

namespace App\Http\Controllers\TgApp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = User::where('telegram_id', $request->uid)->first();
        $token = $user->getToken();

        if(!$user){
            abort(403, 'Вы не авторизованны');
        }
        $products = Product::where('shop_id', $user->shop?->id)
            ->withFilter($request)
            ->withCount('ads')
            ->withSum('ads', 'views_count')
            ->with(['ads' => function($query) {
                $query->withCount([
                    'buybacks as completed_buybacks_count' => function($q) {
                        $q->whereIn('status', ['cashback_received', 'completed']);
                    },
                    'buybacks as proccess_buybacks_count' => function($q) {
                        $q->whereNotIn('status', ['cancelled', 'order_expired', 'cashback_received', 'completed']);
                    }
                ]);
            }])
            ->get();
        return view('app.dashboard.index', compact('user', 'products', 'token'));
    }
}
