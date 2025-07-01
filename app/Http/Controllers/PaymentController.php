<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function handlePay(Request $request)
    {
        $data = $request->all();
        \Log::info('PaymentController handlePay', $data);
        return true;
    }

    public function handleFail(Request $request)
    {
        $data = $request->all();
        \Log::info('PaymentController handleFail', $data);
        return true;
    }

    public function handleCancel(Request $request)
    {
        $data = $request->all();
        \Log::info('PaymentController handleCancel', $data);
        return true;
    }

    public function handle(Request $request)
    {
        $data = $request->all();
        $event = $data['Event'] ?? null;

        if ($event === 'PaymentSucceeded') {
            $invoiceId = $data['Content']['InvoiceId'];
            // Загрузка заказа, пометка как оплаченного
            // Order::where('id',$invoiceId)->update(['status'=>'paid']);
        }
        // другие события...
        return true;
    }
}
