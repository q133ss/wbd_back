<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Services\PaymentService;

class TariffController extends Controller
{
    public function index()
    {
        return Tariff::get();
    }

    public function landingTariffs()
    {
        return Tariff::get();
    }

    public function show(string $buybacks_count)
    {
        $tariff = Tariff::where('buybacks_count', '>=', $buybacks_count)->first();
        if (! $tariff) {
            return Response()->json([
                'status'  => 'false',
                'message' => 'Тариф не найден',
            ], 404);
        }

        return Response()->json([
            'status'  => 'true',
            'message' => 'Тариф найден',
            'tariff'  => $tariff,
        ]);
    }

    public function detail(string $id)
    {
        return Tariff::findOrFail($id);
    }

    public function purchase(string $tariff_id, string $duration)
    {
        $tariff = Tariff::findOrFail($tariff_id);

        $selectedVariant = collect($tariff->data)->firstWhere('duration_days', $duration);

        if(!$selectedVariant){
            abort(404);
        }

        $user = auth('sanctum')->user();
        $hasTariff = $user->tariffs->contains('id', $tariff_id);
        $amount = 0;
        if($hasTariff){
            // вторая цена большая
            $amount = $selectedVariant['recurring_price'];
        }else{
            $amount = $selectedVariant['initial_price'];
        }


        $transaction = Transaction::create([
            'user_id' => auth('sanctum')->id(),
            'amount'  => $amount,
            'transaction_type' => 'deposit',
            'currency_type' => 'buyback',
            'description' => 'Покупка тарифа '.$tariff->name.' на '.$duration.' дней',
            'tariff_id' => $tariff_id,
            'variant' => $selectedVariant
        ]);

        $service = new PaymentService();
        $invoice = $service->createInvoice(
            $amount, // Сумма в копейках (1000 = 10.00 RUB)
            'RUB',
            'Оплата тарифа: '.$tariff->name,
            [
                'Email' => auth('sanctum')->user()->email,
                'InvoiceId' => $transaction->id, // Уникальный идентификатор транзакции
                'successRedirectUrl' => 'https://wbdiscount.pro/payment/success',
                'failRedirectUrl' => 'https://wbdiscount.pro/payment/fail'
            ]
        );

        return response()->json([
            'message' => 'Ссылка для оплаты успешно создана',
            'invoice' => $invoice,
        ]);
    }
}
