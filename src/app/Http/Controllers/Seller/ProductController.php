<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // todo вернуть кол-во выкупов! (12шт /25 шт)
        // Выкуп - это будет модель buyback, у мен
        // вернуть просмотры, выкупы, конверсии, кол-во объявлений
        // todo кеш нельзя тут из-за просмотров!

        // todo Сделать фильтры и дейсвия (архивировать товар,(если у товара есть активные выкупы, сделать архивацию нельзя))
        // todo при архивации возвращаем на баланс деньги

        return auth()->user()->shop?->products()->with('buybacks', 'ads')->paginate();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // update cachce
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // update cachce
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // update cachce
    }
}
