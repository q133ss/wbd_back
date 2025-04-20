<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class SwitchController extends Controller
{
    public function switch()
    {
        $user = auth('sanctum')->user();
        $newRoleSlug = $user->role?->slug === 'buyer' ? 'seller' : 'buyer';

        $user->update([
            'role_id' => Role::where('slug', $newRoleSlug)->value('id')
        ]);

        return response()->json(['message' => true]);
    }
}
