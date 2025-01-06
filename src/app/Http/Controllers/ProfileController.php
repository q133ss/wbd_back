<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileController\UpdateRequest;
use App\Models\Buyback;
use App\Models\File;
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
            ]);
        }
    }

    public function transactions()
    {
        return auth('sanctum')->user()->transactions;
    }

    public function balance()
    {
        $user = auth('sanctum')->user();
        $accessBalance = $user->balance;
        // Это те, которые в выкупах!
        // Типо потенциальный заработок

        $onConfirmation = $user->buybacks()
            ->whereIn('buybacks.status', ['pending', 'awaiting_receipt', 'on_confirmation'])
            ->sum('ads.balance');
        return response()->json([
            'accessBalance' => $accessBalance,
            'onConfirmation' => $onConfirmation
        ]);
    }
}
