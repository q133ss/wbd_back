<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SellerController\UpdateRequest;
use App\Models\Ad;
use App\Models\Buyback;
use App\Models\ImpersonationToken;
use App\Models\Product;
use App\Models\User;
use App\Services\UserDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $sellers = User::with([
                'shop.products.ads', // подгружаем продукты и их объявления
                'buybacks' => fn($q) => $q->whereIn('buybacks.status', ['cashback_received','completed'])
            ])
            ->withFilter($request)
            ->whereHas('role', fn($q) => $q->where('slug', 'seller'))
            ->paginate();

        $sellers->getCollection()->transform(function ($seller) {
            $seller->products_count = $seller->shop ? $seller->shop?->products->count() : 0;
            $seller->ads_count = $seller->shop
                ? $seller->shop->products->sum(fn($product) => $product->ads->count())
                : 0;
            $seller->active_ads_count = $seller->shop
                ? $seller->shop->products->sum(fn($product) => $product->ads->where('status', true)->count())
                : 0;
            $seller->completed_buybacks_count = $seller->buybacks->count();

            return $seller;
        });

        return view('admin.seller.index', compact('sellers'));
    }

    public function show(string $id)
    {
        $user = User::with([
                'shop'
            ])
            ->whereHas('role', fn($q) => $q->where('slug', 'seller'))
            ->findOrFail($id);

        $adsIds = Ad::whereIn('product_id', Product::where('shop_id', $user->shop?->id)->pluck('id')->all())
            ->pluck('id')
            ->all();

        $buybacksProccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
            ->count();
        $buybackSuccess = Buyback::whereIn('ads_id', $adsIds)
            ->whereIn('status', ['cashback_received', 'completed'])
            ->count();

        return view('admin.seller.show', compact('user', 'buybacksProccess', 'buybackSuccess'));
    }

    public function update(UpdateRequest $request, string $id)
    {
        User::findOrFail($id)->update($request->validated());

        return back()->with('success', 'Пользователь успешно обновлен');
    }

    public function delete(string $id, UserDeletionService $userDeletionService)
    {
        try {
            $user = User::whereHas('role', fn($q) => $q->where('slug', 'seller'))
                ->findOrFail($id);

            $userDeletionService->delete($user);

            return redirect()->route('admin.sellers.index')->with('success', 'Продавец удален успешно!');
        }catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.sellers.index')->with('error', 'Ошибка при удалении продавца: ' . $e->getMessage());
        }

    }

    public function unfrozen(string $id)
    {
        User::findOrFail($id)->update(['is_frozen' => false]);
        return back()->with('success', 'Пользователь успешно разморожен');
    }

    public function frozen(string $id)
    {
        User::findOrFail($id)->update(['is_frozen' => true]);
        return back()->with('success', 'Пользователь успешно заморожен');
    }

    public function loginAs(string $id)
    {
        $admin = Auth::user();
        $user = User::findOrFail($id);

        if ($user->role?->slug === 'admin') {
            return back()->with('error', 'Нельзя авторизоваться под администратором');
        }

        $frontendUrl = config('app.frontend_url');

        if (blank($frontendUrl)) {
            return back()->with('error', 'Не настроен адрес фронтенда для авторизации');
        }

        $expiresAt = now()->addMinutes(5);

        ImpersonationToken::query()
            ->where('admin_id', $admin->id)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $plainToken = Str::random(64);

        $impersonation = ImpersonationToken::query()->create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        Log::info('Admin impersonation token created', [
            'impersonation_id' => $impersonation->id,
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        $redirectUrl = rtrim($frontendUrl, '/').'/impersonation?'.http_build_query([
            'impersonation_token' => $plainToken,
            'user' => $user->id,
        ]);

        return redirect()->away($redirectUrl);
    }
}
