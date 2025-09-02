<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use Illuminate\Http\Request;

class BuybacksController extends Controller
{
    public function index(Request $request)
    {
        $buybacks = Buyback::with('ad', 'ad.product', 'user.role')
            ->paginate(20);

        return view('admin.buybacks.index', compact('buybacks'));
    }

    public function show(string $id)
    {
        $buyback = Buyback::findOrFail($id);
        $buyback->load('ad', 'ad.product', 'user.role');

        return view('admin.buybacks.show', compact('buyback'));
    }
}
