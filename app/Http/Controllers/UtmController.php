<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UtmController extends Controller
{
    public function store(Request $request)
    {
        $token = Str::random(32);

        $data = [
            'utm_source'   => $request->get('utm_source'),
            'utm_medium'   => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_content'  => $request->get('utm_content'),
            'utm_term'     => $request->get('utm_term'),
        ];

        Cache::put("utm_{$token}", $data, now()->addDay());

        $botUsername = config('services.telegram.client_username');
        $start = 'utm' . $token;

        if ($ref = $request->get('ref')) {
            $start .= '_ref' . (int) $ref;
        }

        return response()->json([
            'token' => $token,
            'telegram_url' => "https://t.me/{$botUsername}?start={$start}",
        ]);
    }
}
