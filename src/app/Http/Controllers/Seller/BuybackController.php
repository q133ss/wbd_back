<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BuybackController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $buybacks = $user->buybacks()->withFilter($request)->get();

        return response()->json($buybacks);
    }
}
