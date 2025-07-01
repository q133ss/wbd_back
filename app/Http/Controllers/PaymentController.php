<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // [2025-07-01 14:51:39] local.INFO: PaymentController handlePay {"TransactionId":"2922684028","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"1","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:51:38","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f5ead53cf72cd90055e1","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/29","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","AuthCode":"A1B2C3","Token":"tk_f4e858e9bf2a299aa720e92c93a23","TestMode":"1","Status":"Completed","GatewayName":"Test","TotalFee":"0.00","CardProduct":"PRP","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}
    //[2025-07-01 14:52:18] local.INFO: PaymentController handleFail {"TransactionId":"2922685408","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"2","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:52:14","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f61e4f0dd01eb92232a1","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/22","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","TestMode":"1","Status":"Declined","StatusCode":"5","Reason":"AuthenticationFailed","ReasonCode":"5206","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}
    //[2025-07-01 14:52:35] local.INFO: PaymentController handleFail {"TransactionId":"2922685835","Amount":"1900.00","Currency":"RUB","PaymentAmount":"1900.00","PaymentCurrency":"RUB","OperationType":"Payment","InvoiceId":"2","AccountId":"alexey@email.net","SubscriptionId":null,"Name":null,"Email":"alexey@email.net","DateTime":"2025-07-01 14:52:32","IpAddress":"45.130.213.28","IpCountry":null,"IpCity":null,"IpRegion":null,"IpDistrict":null,"IpLatitude":null,"IpLongitude":null,"CardId":"6863f6304f0dd01eb92232a4","CardFirstSix":"220412","CardLastFour":"1661","CardType":"MIR","CardExpDate":"12/32","Issuer":"\"YooMoney\", NBCO LLC","IssuerBankCountry":"RU","Description":"Оплата выкупов. Кол-во: 20","TestMode":"1","Status":"Declined","StatusCode":"5","Reason":"AuthenticationFailed","ReasonCode":"5206","PaymentMethod":null,"InstallmentTerm":null,"InstallmentMonthlyPayment":null,"CustomFields":null}

    private function formatData(array $data, string $type): void
    {
        $transactionId = $data['TransactionId'] ?? null;
        $amount = $data['Amount'] ?? null;
        $dateTime = $data['DateTime'] ?? null; // 2025-07-01 14:51:38
        $ip = $data['IpAddress'] ?? null; // 45.130.213.28
        $descr = $data['Description'] ?? null;

        $invoiceId = $data['InvoiceId'] ?? null;

        Transaction::findOrCreate(
            ['transaction_id' => $transactionId],
            [
                'amount' => $amount,
                'date_time' => $dateTime,
                'ip_address' => $ip,
                'description' => $descr,
                'invoice_id' => $invoiceId,
                'status' => $type,
            ]
        );
    }
    public function handlePay(Request $request)
    {
        $data = $request->all();
        $this->formatData($data, 'completed');
        return true;
    }

    public function handleFail(Request $request)
    {
        $data = $request->all();
        $this->formatData($data, 'failed');
        return true;
    }

    public function handleCancel(Request $request)
    {
        $data = $request->all();
        $this->formatData($data, 'failed');
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
