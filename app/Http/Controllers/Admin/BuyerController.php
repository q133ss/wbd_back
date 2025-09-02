<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.buyer.show', compact('user'));
    }

    public function payments()
    {

    }

    public function buybacks()
    {

    }
}
