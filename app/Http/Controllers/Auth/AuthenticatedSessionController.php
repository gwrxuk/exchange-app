<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\LoginData;
use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\LogoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginData $data): JsonResponse
    {
        if (! Auth::attempt($data->only('email', 'password')->toArray(), $data->remember)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        request()->session()->regenerate();

        return response()->json([
            'message' => 'Authenticated',
            'user' => UserData::from(request()->user()),
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
