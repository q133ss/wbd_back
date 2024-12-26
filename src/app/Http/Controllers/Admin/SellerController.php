<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class SellerController extends Controller
{
    public function index()
    {
        $sellers = User::where('role_id', function ($query) {
            return $query->select('id')
                ->from('roles')
                ->where('slug', 'seller');
        })->paginate();

        return view('admin.seller.index', compact('sellers'));
    }
}
