<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileController\AvatarRequest;
use App\Http\Requests\ProfileController\PhoneCodeRequest;
use App\Http\Requests\ProfileController\TopupBuybacksRequest;
use App\Http\Requests\ProfileController\TopUpRequest;
use App\Http\Requests\ProfileController\UpdatePhoneRequest;
use App\Http\Requests\ProfileController\UpdateRequest;
use App\Http\Requests\ProfileController\WithdrawRequest;
use App\Models\Buyback;
use App\Models\Cashout;
use App\Models\PhoneVerification;
use App\Models\ReferralStat;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
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
        $accessBalance    = $user->balance;
        $role             = auth('sanctum')->user()->role;
        $redemption_count = $user->redemption_count;

        $data = [
            'accessBalance'    => $accessBalance,
            'onConfirmation'   => 0, // На подтверждении, либо заморожено
            'redemption_count' => $redemption_count,
        ];

        if ($role->slug == 'buyer') {
            // Покупатель
            $onConfirmation = $user->buybacks()->whereIn('buybacks.status', [
                'pending',
                'awaiting_receipt',
                'on_confirmation',
            ])->sum('buybacks.price');
        } else {
            // Продавец
            $today           = Carbon::today();
            $yesterday       = Carbon::yesterday();
            $last7Days       = Carbon::now()->subDays(7);
            $transactionData = Transaction::where('user_id', $user->id)
                ->where('transaction_type', 'withdraw')
                ->where('currency_type', 'cash')
                ->select([
                    DB::raw("SUM(CASE WHEN DATE(created_at) = '{$today}' THEN amount ELSE 0 END) as today"),
                    DB::raw("SUM(CASE WHEN DATE(created_at) = '{$yesterday}' THEN amount ELSE 0 END) as yesterday"),
                    DB::raw("SUM(CASE WHEN created_at >= '{$last7Days}' THEN amount ELSE 0 END) as last_7_days"),
                ])
                ->first();
            $onConfirmation          = $user->frozenBalance()->where('status', 'reserved')->sum('amount');
            $data['transactionData'] = $transactionData;
        }
        $data['onConfirmation'] = $onConfirmation;

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

        $completedBuybacks = $user->buybacks?->where('status', 'completed');
        $cashbackPaid = $completedBuybacks->sum('price');

        if ($user->isSeller()) {
            // Продавец
            $successBuybacks = Buyback::leftJoin('ads', 'ads.id', '=', 'buybacks.ads_id')
                ->selectRaw('ROUND((SUM(CASE WHEN buybacks.status = "completed" THEN 1 ELSE 0 END) / COUNT(buybacks.id)) * 100, 1) as percentage')
                ->where('ads.user_id', $user->id)
                ->first();

            $productRating = Review::join('products', 'products.id', '=', 'reviews.reviewable_id')
                ->where('products.shop_id', function ($query) use ($user) {
                    return $query->select('id')
                        ->from('shops')
                        ->where('shops.user_id', $user->id);
                })
                ->where('reviews.reviewable_type', 'App\Models\Product');
        } else {
            // Покупатель
            $successBuybacks = Buyback::selectRaw('ROUND((SUM(CASE WHEN buybacks.status = "completed" THEN 1 ELSE 0 END) / COUNT(buybacks.id)) * 100, 1) as percentage')
                ->where('buybacks.user_id', $user->id)
                ->first();
            $productRating = Review::where('reviews.user_id', $user->id)
                ->where('reviews.reviewable_type', 'App\Models\Product')
                ->orWhere('reviews.reviewable_type', 'App\Models\Ad');
        }

        $userData['success_buybacks'] = round($successBuybacks->percentage, 1); // % успешных выкупов
        $userData['cashback_paid']    = round($cashbackPaid, 1); // Кол-во выплаченного кешбека
        $userData['total_reviews']    = round($productRating->count(), 1); // Кол-во оценок товаров
        $userData['product_rating']   = round($productRating->avg('reviews.rating'), 1); // Рейтинг товаров

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

    public function topupBuybacks(TopupBuybacksRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
            DB::beginTransaction();
            $user = auth('sanctum')->user();
            $amount = $request->amount;
            $sum = Tariff::where('buybacks_count', $amount)->pluck('price')->first();
            $user->update([
                'balance' => $user->balance -= $sum,
                'redemption_count' => $user->redemption_count += $amount
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Баланс успешно пополнен!',
                'balance' => $user->balance,
                'redemption_count' => $user->redemption_count
            ]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Произошла ошибка, попробуйте еще раз'
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
}
