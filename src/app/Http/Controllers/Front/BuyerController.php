<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;

class BuyerController extends Controller
{
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->isSeller()) {
            abort(404);
        }
        $userArr            = $user->toArray();
        $userArr['reviews'] = Review::where('user_id', $user->id)->get();

        return $userArr;
    }
}
