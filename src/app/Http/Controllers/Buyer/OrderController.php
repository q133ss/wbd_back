<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Services\Buyer\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(string $ad_id)
    {
        return (new OrderService())->createOrder($ad_id);
    }
}
