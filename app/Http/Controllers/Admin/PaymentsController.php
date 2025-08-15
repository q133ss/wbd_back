<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index()
    {
        $transactions = Transaction::paginate(20);

        return view('admin.seller.payments.index', compact('transactions'));
    }
}
