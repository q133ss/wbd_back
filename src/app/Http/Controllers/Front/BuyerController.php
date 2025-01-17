<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function show(string $id)
    {
        return User::findOrFail($id);
    }
}
