<?php

namespace App\Http\Controllers\Auth;

use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\LogoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Authenticated',
            'user' => UserData::from($request->user()),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(LogoutRequest $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
