<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BuybackController extends Controller
{
    public function index()
    {
        return auth()->user()->buybacks;
    }
}
