<?php

namespace App\Http\Controllers;

use App\Http\Requests\Impersonation\ExchangeRequest;
use App\Models\ImpersonationToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    public function exchange(ExchangeRequest $request): JsonResponse
    {
        $plainToken = $request->validated('token');
        $tokenHash = hash('sha256', $plainToken);

        $impersonation = ImpersonationToken::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->first();

        if (! $impersonation || $impersonation->expires_at->isPast()) {
            return response()->json([
                'message' => 'Ссылка недействительна или устарела',
            ], 422);
        }

        $user = User::query()->find($impersonation->user_id);

        if (! $user) {
            $impersonation->delete();

            return response()->json([
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $token = $user->createToken(
            name: 'impersonation-admin-'.$impersonation->admin_id,
            abilities: ['impersonation'],
            expiresAt: now()->addHours(2)
        );

        $impersonation->forceFill([
            'used_at' => now(),
            'used_ip' => $request->ip(),
            'used_user_agent' => $request->userAgent(),
        ])->save();

        Log::info('Admin impersonation exchange completed', [
            'admin_id' => $impersonation->admin_id,
            'user_id' => $user->id,
            'impersonation_id' => $impersonation->id,
        ]);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }
}
