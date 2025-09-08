<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SellerController\UpdateRequest;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function index(Request $request)
    {
        $buyers = User::where('role_id', Role::where('slug', 'buyer')->pluck('id')->first())->withFilter($request)->with('buybacks')->paginate();
        return view('admin.buyer.index', compact('buyers'));
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);

        $adsIds = Ad::whereIn('id', Buyback::where('user_id', $user->id)->pluck('ads_id')->all())
            ->pluck('id')
            ->all();

        $adsIds = Ad::whereIn('id', Buyback::where('user_id', $user->id)->pluck('ads_id')->all())
            ->pluck('id')
            ->all();
        $buybacksProccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
            ->count();

        $buybackSuccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['cashback_received', 'completed'])
            ->count();

        return view('admin.buyer.show', compact('user', 'buybackSuccess', 'buybacksProccess'));
    }

    public function update(UpdateRequest $request, string $id)
    {
        User::findOrFail($id)->update($request->validated());
        return back()->with('success', 'Комментарий успешно обновлен');
    }

    public function buybacks(Request $request)
    {
        $buybacks = Buyback::withFilter($request)->paginate();

        return view('admin.buyer.buybacks', compact('buybacks'));
    }

}
