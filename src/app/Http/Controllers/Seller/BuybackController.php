<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuybackController\ShowResource;
use App\Models\Buyback;
use Illuminate\Http\Request;

class BuybackController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth('sanctum')->user();
        $buybacks = $user->buybacks()->withFilter($request)->get();

        return response()->json($buybacks);
    }

    public function show(string $id)
    {
        $userId = auth('sanctum')->id();
        $buyback = Buyback::with('messages')
            ->leftJoin('users', 'buybacks.user_id', '=', 'users.id')
            ->leftJoin('ads', 'buybacks.ads_id', '=', 'ads.id')
            ->where('buybacks.id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('buybacks.user_id', $userId)
                    ->orWhere('ads.user_id', $userId);
            })
            ->select('buybacks.*')
            ->first();
        if ($buyback === null) {
            abort(404);
        }
        return new ShowResource($buyback);
    }
}
