<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->attributes->get('kc_user');
        $roles = $request->attributes->get('kc_roles', []);

        // Debug log for roles and user when /api/me is called
        Log::info('Keycloak /api/me', [
            'preferred_username' => $user['preferred_username'] ?? null,
            'sub' => $user['sub'] ?? null,
            'roles' => $roles,
        ]);

        return response()->json([
            'user' => $user,
            'roles' => $roles,
        ]);
    }
}
