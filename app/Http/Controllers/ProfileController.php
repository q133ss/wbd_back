<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileController\AvatarRequest;
use App\Http\Requests\ProfileController\PhoneCodeRequest;
use App\Http\Requests\ProfileController\TopupBuybacksRequest;
use App\Http\Requests\ProfileController\TopUpRequest;
use App\Http\Requests\ProfileController\UpdatePaymentRequest;
use App\Http\Requests\ProfileController\UpdatePhoneRequest;
use App\Http\Requests\ProfileController\UpdateRequest;
use App\Http\Requests\ProfileController\WithdrawRequest;
use App\Jobs\CheckPayJob;
use App\Models\Buyback;
use App\Models\Cashout;
use App\Models\PaymentMethod;
use App\Models\PhoneVerification;
use App\Models\ReferralStat;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(PaymentService $pay)
    {
        $this->pay = $pay;
    }

    public function index()
    {
        return auth('sanctum')->user();
    }

    public function update(UpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = auth('sanctum')->user();
            $data = $request->validated();
            if ($request->has('password')) {
                $data['password'] = Hash::make($request->password);
            }
            $user->update($data);

            if ($request->hasFile('avatar')) {
                // Удаление предыдущего аватара, если он существует
                if ($user->avatar) {
                    $oldFile = storage_path('app/public/'.$user->avatar?->getRawOriginal('src'));
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                    $user->avatar?->delete();
                }

                $fileSrc = $request->file('avatar')->store('avatars', 'public');
                $user->avatar()->create([
                    'src'      => $fileSrc,
                    'category' => 'avatar',
                ]);
            }

            DB::commit();

            $user->load('avatar');

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function transactions(Request $request)
    {
        return auth('sanctum')
            ->user()
            ->transactions()
            ->withFilter($request)
            ->paginate();
    }

    public function balance()
    {
        $user             = auth('sanctum')->user();
        $role             = auth('sanctum')->user()->role;
        $redemption_count = $user->redemption_count;

        $data = [
            'redemption_count' => $redemption_count,
        ];

        // Продавец
        $today           = Carbon::today();
        $yesterday       = Carbon::yesterday();
        $last7Days       = Carbon::now()->subDays(7);
        $transactionData = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdraw')
            ->where('currency_type', 'buyback')
            ->select([
                DB::raw("SUM(CASE WHEN DATE(created_at) = '{$today}' THEN amount ELSE 0 END) as today"),
                DB::raw("SUM(CASE WHEN DATE(created_at) = '{$yesterday}' THEN amount ELSE 0 END) as yesterday"),
                DB::raw("SUM(CASE WHEN created_at >= '{$last7Days}' THEN amount ELSE 0 END) as last_7_days"),
            ])
            ->first();
        $data['transactionData'] = $transactionData;

        return response()->json($data);
    }

    public function withdraw(WithdrawRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = auth('sanctum')->user();
            $user->update([
                'balance' => $user->balance -= $request->amount,
            ]);
            $cashout = Cashout::create([
                'user_id'     => $user->id,
                'amount'      => $request->amount,
                'card_number' => $request->card_number,
            ]);
            \Log::channel('paylog')->info('Юзер ID:'.$user->id.' Заказал вывод денег. CashoutID'.$cashout->id);
            DB::commit();

            return response()->json([
                'status'       => 'true',
                'message'      => 'Заявка на вывод успешно создана!',
                'user_balance' => $user->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function withdraws()
    {
        return auth('sanctum')->user()->withdraws;
    }

    public function withdrawCancel(string $id)
    {
        $cashout = Cashout::where('user_id', auth('sanctum')->id())->findOrFail($id);
        try {
            DB::beginTransaction();
            $user = auth('sanctum')->user();
            $user->update([
                'balance' => $user->balance += $cashout->amount,
            ]);
            $cashout->update(['is_archived' => true]);
            DB::commit();
            \Log::channel('paylog')->info('Юзер ID:'.$user->id.' Отменил вывод денег. CashoutID:'.$cashout->id);

            return response()->json([
                'status'       => 'true',
                'message'      => 'Заявка на вывод успешно отменена!',
                'user_balance' => $user->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }

    public function statistic()
    {
        $user     = auth('sanctum')->user();
        $userData = [];

        $completedBuybacks = $user->buybacks?->whereIn('status', ['cashback_received', 'completed']);
        $cashbackPaid = $completedBuybacks->sum(function ($buyback) {
            return $buyback->product_price - $buyback->price_with_cashback;
        });

        $userRating = Review::where('reviews.reviewable_type', 'App\Models\User')
            ->where('reviews.reviewable_id', $user->id)
            ->selectRaw('AVG(reviews.rating) as rating')
            ->first();

        if ($user->isSeller()) {
            // Продавец
            $successBuybacks = Buyback::leftJoin('ads', 'ads.id', '=', 'buybacks.ads_id')
                ->selectRaw('ROUND((SUM(CASE WHEN buybacks.status IN ("cashback_received", "completed") THEN 1 ELSE 0 END) / COUNT(buybacks.id)) * 100, 1) as percentage')
                ->where('ads.user_id', $user->id)
                ->first();

            $averageResponseTime = DB::table('messages as m1')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_response_time')
                ->join('messages as m2', function($join) use ($user) {
                    $join->on('m1.buyback_id', '=', 'm2.buyback_id')
                        ->where('m2.sender_id', '=', $user->id)  // Ответ продавца
                        ->where('m2.created_at', '>', DB::raw('m1.created_at'));
                })
                ->where('m1.sender_id', '!=', $user->id)
                ->whereIn('m1.buyback_id', function($query) use ($user) {
                    $query->select('buybacks.id')
                        ->from('buybacks')
                        ->join('ads', 'ads.id', '=', 'buybacks.ads_id')
                        ->where('ads.user_id', $user->id);
                })
                ->first()
                ->avg_response_time ?? 0;
        } else {
            // Покупатель
            $successBuybacks = Buyback::selectRaw('
                ROUND((
                    SUM(CASE
                        WHEN buybacks.status IN ("completed", "cashback_received") THEN 1
                        ELSE 0
                    END) /
                    COUNT(buybacks.id)
                * 100, 1) as percentage')
                ->where('buybacks.user_id', $user->id)
                ->first();

            // Среднее время ответа покупателя (разница между сообщением продавца и ответом покупателя)
            $averageResponseTime = DB::table('messages as m1')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_response_time')
                ->join('messages as m2', function($join) use ($user) {
                    $join->on('m1.buyback_id', '=', 'm2.buyback_id')
                        ->where('m2.sender_id', '=', $user->id)  // Ответ покупателя
                        ->where('m2.created_at', '>', DB::raw('m1.created_at'));
                })
                ->where('m1.sender_id', '!=', $user->id)
                ->whereIn('m1.buyback_id', function($query) use ($user) {
                    $query->select('id')
                        ->from('buybacks')
                        ->where('user_id', $user->id);
                })
                ->first()
                ->avg_response_time ?? 0;
        }

        $userData['success_buybacks'] = round($successBuybacks->percentage); // % успешных выкупов
        $userData['cashback_paid']    = round($cashbackPaid); // Кол-во выплаченного кешбека
        $userData['user_rating']   = round($userRating->rating,1) ?? 0; // Рейтинг пользователя
        $userData['average_response_time']   = round($averageResponseTime / 60, 1) ?? 0; // Среднее время ответа в минутах

        return $userData;
    }

    public function avatar(AvatarRequest $request)
    {
        $user = auth('sanctum')->user();
        if ($request->hasFile('avatar')) {
            // Удаление предыдущего аватара, если он существует
            if ($user->avatar) {
                $oldFile = storage_path('app/public/'.$user->avatar?->getRawOriginal('src'));
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
                $user->avatar?->delete();
            }

            $fileSrc = $request->file('avatar')->store('avatars', 'public');
            $user->avatar()->create([
                'src'      => $fileSrc,
                'category' => 'avatar',
            ]);

            return response()->json([
                'status'  => 'true',
                'message' => 'Аватар успешно изменен!',
            ]);
        }
    }

    public function topup(TopUpRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        $user->update(['balance' => $user->balance += $request->amount]);
        // todo чек
        $refState = ReferralStat::where(['user_id' => $user->referral_id])->first();
        if($refState) {
            // Рассчитываем 10% от $request->amount
            $bonusAmount = $request->amount * 0.1;
            $refState->update([
                'topup_count' => $refState->topup_count + 1,
                'earnings' => $refState->earnings + $bonusAmount
            ]);
        }
        return response()->json([
            'message' => 'Баланс успешно пополнен!'
        ]);
    }

    public function topupBuybacks(string $tariff_id): \Illuminate\Http\JsonResponse
    {
        $tariff = Tariff::findOrFail($tariff_id);

        $transaction = Transaction::create([
            'user_id' => auth('sanctum')->id(),
            'amount'  => $tariff->price,
            'transaction_type' => 'deposit',
            'currency_type' => 'buyback',
            'description' => 'Пополнение баланса на '.$tariff->buybacks_count.' выкупов'
        ]);

        $invoice = $this->pay->createInvoice(
            $tariff->price, // Сумма в копейках (1000 = 10.00 RUB)
            'RUB',
            'Оплата выкупов. Кол-во: '.$tariff->buybacks_count,
            [
                'Email' => auth('sanctum')->user()->email,
                'InvoiceId' => $transaction->id, // Уникальный идентификатор транзакции
                'successRedirectUrl' => 'https://wbdiscount.pro/payment/success',
                'failRedirectUrl' => 'https://wbdiscount.pro/payment/fail'
            ]
        );

        if(isset($invoice['Id'])){
            return response()->json([
                'message' => 'Ссылка для оплаты успешно создана',
                'invoice' => $invoice,
            ]);
        }else{
            return response()->json([
                'message' => 'Произошла ошибка при создании ссылки для оплаты',
            ], 500);
        }
    }

    public function phoneCode(PhoneCodeRequest $request)
    {
        $verification = PhoneVerification::where('phone_number', $request->phone)->first();
        $code         = random_int(1000, 9999);

        if ($verification) {
            $verification->update([
                'verification_code' => $code,
                'expires_at'        => now()->addMinutes(10),
            ]);
        } else {
            PhoneVerification::create([
                'phone_number'      => $request->phone,
                'verification_code' => $code,
                'expires_at'        => now()->addMinutes(10),
            ]);
        }

        $smsService = new SmsService;
        $send       = $smsService->send($request->phone, $code);

        if (! $send) {
            return Response()->json([
                'message' => 'При отправке СМС произошла ошибка',
            ], 500);
        }

        return Response()->json([
            'message' => 'Код успешно отправлен',
        ]);
    }

    public function updatePhone(UpdatePhoneRequest $request)
    {
        $user = auth('sanctum')->user();
        $user->update([
            'phone' => $request->phone,
        ]);
        $verification = PhoneVerification::where('phone_number', $request->phone)->first();
        if ($verification) {
            $verification->delete();
        }

        return response()->json([
            'status'  => 'true',
            'message' => 'Телефон успешно изменен!',
        ]);
    }

    public function onlyBalance()
    {
        $user             = auth('sanctum')->user();
        $accessBalance    = $user->balance;
        $redemption_count = $user->redemption_count;

        $data = [
            'accessBalance'    => $accessBalance,
            'redemption_count' => $redemption_count,
        ];

        return response()->json($data);
    }

    public function getPayment()
    {
        return auth('sanctum')->user()->paymentMethod;
    }
    public function updatePayment(UpdatePaymentRequest $request)
    {
        PaymentMethod::updateOrCreate(
            ['user_id' => auth('sanctum')->id()],
            [
                'sbp' => $request->input('sbp'),
                'sbp_comment' => $request->input('sbp_comment'),
                'sber' => $request->input('sber'),
                'tbank' => $request->input('tbank'),
                'ozon' => $request->input('ozon'),
                'alfa' => $request->input('alfa'),
                'vtb' => $request->input('vtb'),
                'raiffeisen' => $request->input('raiffeisen'),
                'gazprombank' => $request->input('gazprombank')
            ]
        );
        return response()->json([
            'status'  => 'true',
            'message' => 'Способ оплаты успешно обновлен!',
        ]);
    }
}
