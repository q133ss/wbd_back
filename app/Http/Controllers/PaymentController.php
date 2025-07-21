<?php

namespace App\Http\Controllers;

use App\Models\ReferralStat;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // [2025-07-01 14:51:39] local.INFO: PaymentController handlePay {"TransactionId":"2922684028","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"1","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:51:38","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f5ead53cf72cd90055e1","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/29","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","AuthCode":"A1B2C3","Token":"tk_f4e858e9bf2a299aa720e92c93a23","TestMode":"1","Status":"Completed","GatewayName":"Test","TotalFee":"0.00","CardProduct":"PRP","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}
    //[2025-07-01 14:52:18] local.INFO: PaymentController handleFail {"TransactionId":"2922685408","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"2","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:52:14","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f61e4f0dd01eb92232a1","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/22","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","TestMode":"1","Status":"Declined","StatusCode":"5","Reason":"AuthenticationFailed","ReasonCode":"5206","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}
    //[2025-07-01 14:52:35] local.INFO: PaymentController handleFail {"TransactionId":"2922685835","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"2","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:52:32","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f6304f0dd01eb92232a4","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/32","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","TestMode":"1","Status":"Declined","StatusCode":"5","Reason":"AuthenticationFailed","ReasonCode":"5206","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}

    private function updateTransaction(array $data, string $type)
    {
        $transactionId = $data['TransactionId'] ?? null;
        $amount = $data['Amount'] ?? null;
        $dateTime = $data['DateTime'] ?? null; // 2025-07-01 14:51:38
        $ip = $data['IpAddress'] ?? null; // 45.130.213.28
        $descr = $data['Description'] ?? null;

        $invoiceId = $data['InvoiceId'] ?? null;

        $transaction = Transaction::find($invoiceId);
        $updated = $transaction->update(
            [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'date_time' => $dateTime,
                'ip_address' => $ip,
                'description' => $descr,
                'status' => $type
            ]);

        return $transaction;
    }

    private function test()
    {
        $transaction = Transaction::find(1);
        $updated = $transaction->update(['transaction_id' => 111]);
        return $transaction;
    }



    public function handlePay(Request $request)
    {
        $data = $request->all();

        $transaction = $this->updateTransaction($data, 'completed');

        try{
            DB::beginTransaction();

            $user = User::findOrFail($transaction->user_id);
            $amount = $transaction->amount;

            $tariff = Tariff::findOrFail($transaction['tariff_id']);
            $variant = $transaction['variant'];

            $duration = $variant['duration_days'];

            $selectedVariant = collect($tariff->data)->firstWhere('duration_days', $duration);

            $user->tariffs()->create([
                'end_date' => now()->addDays($duration),
                'tariff_id'     => $tariff->id,
                'variant_name'  => $selectedVariant['name'],
                'duration_days' => $selectedVariant['duration_days'],
                'price_paid'    => $selectedVariant['initial_price'],
                'starts_at'     => now(),
                'ends_at'       => now()->addDays($selectedVariant['duration_days']),
            ]);

            $refState = ReferralStat::where(['user_id' => $user->referral_id])->first();
            if($refState) {
                // Рассчитываем 10% от $request->amount
                $bonusAmount = $request->amount * 0.1;
                $refState->update([
                    'topup_count' => $refState->topup_count + 1,
                    'earnings' => $refState->earnings + $bonusAmount
                ]);
            }

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();
            \Log::error('PaymentController handlePay error: ' . $e->getMessage());
        }
    }

    public function handleFail(Request $request)
    {
        $data = $request->all();
        $this->updateTransaction($data, 'failed');
        return true;
    }

    public function handleCancel(Request $request)
    {
        $data = $request->all();
        $this->updateTransaction($data, 'failed');
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
