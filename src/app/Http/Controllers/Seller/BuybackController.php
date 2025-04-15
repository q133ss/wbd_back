<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuybackController\ShowResource;
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
        $buyback = auth('sanctum')->user()->buybacks?->where('id', $id)->first();
        if($buyback == null){
            abort(404);
        }

        return new ShowResource($buyback);
    }
}
