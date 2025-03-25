<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Redis;

class CartFavoriteController extends Controller
{
    public function add(Request $request, $type)
    {
        $userId    = auth('sanctum')->id();
        $productId = $request->input('product_id');
        $quantity  = $request->input('quantity', 1);

        // Проверка существования товара
        $product = Ad::findOrFail($productId);

        // Обработка в зависимости от типа
        if ($type === 'cart') {
            // Использование Redis для временного хранения данных корзины
            $cartKey = "cart:$userId";
            Redis::hIncrBy($cartKey, $productId, $quantity);

            return response()->json([
                'message' => 'Товар добавлен в корзину!',
                'ad'      => $product,
            ]);
        } elseif ($type === 'favorite') {
            // Использование Redis для временного хранения данных избранного
            $favoriteKey = "favorite:$userId";
            Redis::sAdd($favoriteKey, $productId);
            $product->in_favorite = $product->in_favorite + 1;
            $product->save();

            return response()->json([
                'message' => 'Товар добавлен в избранное',
                'ad'      => $product,
            ]);
        } else {
            return response()->json(['message' => 'Неверный тип'], 400);
        }
    }

    // Метод для просмотра корзины
    public function viewCart(Request $request)
    {
        $userId  = auth('sanctum')->id();
        $cartKey = "cart:$userId";

        // Получение данных корзины из Redis
        $cartItems = Redis::hGetAll($cartKey);
        $products  = Ad::whereIn('id', array_keys($cartItems))->get();

        $cart = [];
        foreach ($products as $product) {
            $cart[] = [
                'product'  => $product,
                'quantity' => $cartItems[$product->id],
                'total'    => $product->price_with_cashback * $cartItems[$product->id],
            ];
        }

        return response()->json(['cart' => $cart]);
    }

    // Метод для просмотра избранного
    public function viewFavorites(Request $request)
    {
        $userId      = $request->user()->id;
        $favoriteKey = "favorite:$userId";

        // Получение данных избранного из Redis
        $favoriteItems = Redis::sMembers($favoriteKey);
        $products      = Ad::whereIn('id', $favoriteItems)->get();

        $favorites = [];
        foreach ($products as $product) {
            $favorites[] = [
                'product' => $product,
                'name'    => $product->name,
                'price'   => $product->price_with_cashback,
            ];
        }

        return response()->json(['favorites' => $favorites]);
    }

    // Метод для удаления товара из корзины или избранного
    public function remove(Request $request, $type)
    {
        $userId    = auth('sanctum')->id();
        $productId = $request->input('product_id');

        // Обработка в зависимости от типа
        if ($type === 'cart') {
            $cartKey = "cart:$userId";
            Redis::hDel($cartKey, $productId);

            return response()->json(['message' => 'Товар удален из корзины']);
        } elseif ($type === 'favorite') {
            $favoriteKey = "favorite:$userId";
            Redis::sRem($favoriteKey, $productId);

            return response()->json(['message' => 'Товар удален из избранного']);
        } else {
            return response()->json(['message' => 'Неверный тип'], 400);
        }
    }
}
