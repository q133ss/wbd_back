<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\PromocodeController\ApplyRequest;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromocodeController extends Controller
{
    public function apply(ApplyRequest $request)
    {
        DB::beginTransaction();
        try {
            $promocode = Promocode::where('promocode', $request->promocode)->first();
            $user = auth('sanctum')->user();
            $user->promocodes()->attach($promocode->id);
            $user->update([
                'redemption_count' => $user->redemption_count += $promocode->buybacks_count
            ]);
            DB::commit();
            return response()->json(['message' => 'Промокод применен']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Произошла ошибка при применении промокода'], 500);
        }
    }
}
