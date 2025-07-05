<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Tariff;

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
}
