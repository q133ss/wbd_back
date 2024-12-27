<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\AdsController\StoreRequest;
use App\Http\Requests\Seller\AdsController\UpdateRequest;
use App\Models\Ad;
use App\Services\Seller\AdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ads = Ad::where('user_id', auth('sanctum')->id())
            ->withFilter($request)
            ->withCount(['buybacks' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->paginate();

        $ads->getCollection()->transform(function ($ad) {
            $ad->completed_buybacks_count = $ad->buybacks_count; // Кол-во завершённых выкупов
            unset($ad->buybacks_count);
            $ad->balance = '???';
            $ad->in_deal = '???'; // В сделках
            $cr          = ceil($ad->completed_buybacks_count / max($ad->redemption_count, 1)); // Защита от деления на 0
            $ad->cr      = $cr;

            return $ad;
        });

        return response()->json($ads);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return (new AdsService)->create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Ad::where('user_id', auth('sanctum')->id())->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $ad = Ad::where('user_id', auth('sanctum')->id())
            ->findOrFail($id);
        $update = $ad->update($request->validated());

        return $ad;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $ad = Ad::where('user_id', auth('sanctum')->id())
                ->findOrFail($id);

            $user = auth('sanctum')->user();
            $user->update([
                'redemption_count' => $user->redemption_count + $ad->redemption_count,
            ]);

            $update = $ad->update([
                'is_archived'      => true,
                'redemption_count' => 0,
            ]);
            DB::commit();

            return Response()->json([
                'status'  => 'true',
                'message' => 'Объявление архивировано',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'false',
                'message' => 'Произошла ошибка, попробуйте еще раз',
            ]);
        }
    }

    public function archive()
    {
        // todo Сделать фильтры и дейсвия (архивировать товар,(если у товара есть активные выкупы, сделать архивацию нельзя))
        return Ad::withoutArchived()
            ->where('user_id', auth('sanctum')->id())
            ->get();
    }

    /**
     * Выкупы в процессе
     *
     * @return mixed
     */
    public function process()
    {
        $buybacks = Ad::where('user_id', auth('sanctum')->id())->sum('redemption_count');

        return response()->json([
            'buybacks' => $buybacks,
        ]);
    }
}
