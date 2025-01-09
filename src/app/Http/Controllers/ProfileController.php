<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileController\UpdateRequest;
use App\Http\Requests\ProfileController\WithdrawRequest;
use App\Models\Buyback;
use App\Models\Cashout;
use App\Models\File;
use App\Models\Role;
use App\Models\Transaction;
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
            $data['password'] = Hash::make($request->password);
            $user->update($data);

            if ($request->hasFile('avatar')) {
                // Удаление предыдущего аватара, если он существует
                if ($user->avatar) {
                    $oldFile = storage_path('app/public/' . $user->avatar?->getRawOriginal('src'));
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                    $user->avatar?->delete();
                }

                $fileSrc = $request->file('avatar')->store('avatars', 'public');
                $user->avatar()->create([
                    'src' => $fileSrc,
                    'category' => 'avatar'
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
            ->get();
    }

    public function balance()
    {
        $user = auth('sanctum')->user();
        $accessBalance = $user->balance;
        $role = auth('sanctum')->user()->role;

        $data = [
            'accessBalance' => $accessBalance,
            'onConfirmation' => 0 // На подтверждении, либо заморожено
        ];

        if($role->slug == 'buyer')
        {
            // Покупатель
            $onConfirmation = $user->buybacks()->whereIn('buybacks.status', [
                'pending',
                'awaiting_receipt',
                'on_confirmation'
            ])->sum('buybacks.price');
        }else{
            // Продавец
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $last7Days = Carbon::now()->subDays(7);
            $transactionData = Transaction::where('user_id', $user->id)
                ->where('transaction_type', 'withdraw')
                ->where('currency_type', 'cash')
                ->select([
                    DB::raw("SUM(CASE WHEN DATE(created_at) = '{$today}' THEN amount ELSE 0 END) as today"),
                    DB::raw("SUM(CASE WHEN DATE(created_at) = '{$yesterday}' THEN amount ELSE 0 END) as yesterday"),
                    DB::raw("SUM(CASE WHEN created_at >= '{$last7Days}' THEN amount ELSE 0 END) as last_7_days"),
                ])
                ->first();
            $onConfirmation = $user->frozenBalance()->where('status','reserved')->sum('amount');
            $data['transactionData'] = $transactionData;
        }
        $data['onConfirmation'] = $onConfirmation;
        return response()->json($data);
    }

    public function withdraw(WithdrawRequest $request)
    {
        try{
            DB::beginTransaction();
            $user = auth('sanctum')->user();
            $user->update([
                'balance' => $user->balance -= $request->amount
            ]);
            $cashout = Cashout::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'card_number' => $request->card_number
            ]);
            \Log::channel('paylog')->info('Юзер ID:'.$user->id.' Заказал вывод денег. CashoutID'.$cashout->id);
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'Заявка на вывод успешно создана!',
                'user_balance' => $user->balance
            ]);
        }catch (\Exception $e){
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
        try{
            DB::beginTransaction();
            $user = auth('sanctum')->user();
            $user->update([
                'balance' => $user->balance += $cashout->amount
            ]);
            $cashout->update(['is_archived' => true]);
            DB::commit();
            \Log::channel('paylog')->info('Юзер ID:'.$user->id.' Отменил вывод денег. CashoutID:'.$cashout->id);
            return response()->json([
                'status' => 'true',
                'message' => 'Заявка на вывод успешно отменена!',
                'user_balance' => $user->balance
            ]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ], 500);
        }
    }
}
