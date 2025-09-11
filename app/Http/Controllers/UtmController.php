<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UtmController extends Controller
{
    public function store(Request $request)
    {
        $ip = $request->header('X-Forwarded-For') ?? $request->ip();

        $utmData = $request->only([
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]);

        Cache::put("utm_{$ip}", $utmData, now()->addDay());

        return response()->json(['message' => 'true']);
    }
}
