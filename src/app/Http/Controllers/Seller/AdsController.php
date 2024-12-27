<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\AdsController\StoreRequest;
use App\Http\Requests\Seller\AdsController\UpdateRequest;
use App\Models\Ad;
use App\Services\Seller\AdsService;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Ad::where('user_id', auth('sanctum')->id())
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        return (new AdsService())->create($request->validated());
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
        $ad = Ad::where('user_id', auth('sanctum')->id())
            ->findOrFail($id);
        $update = $ad->update(['is_archived' => true]);
        return Response()->json([
            'status' => 'true',
            'message' => 'Объявление архивировано'
        ]);
    }

    public function archive()
    {
        return Ad::withoutArchived()
            ->where('user_id', auth('sanctum')->id())
            ->get();
    }

    /**
     * Выкупы в процессе
     * @return mixed
     */
    public function process()
    {
        $buybacks = Ad::where('user_id', auth('sanctum')->id())->sum('redemption_count');
        return response()->json([
            'buybacks' => $buybacks
        ]);
    }
}
