<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\ConfirmPasswordData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * Confirm the user's password.
     */
    public function store(ConfirmPasswordData $data): JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => request()->user()->email,
            'password' => $data->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        request()->session()->put('auth.password_confirmed_at', time());

        return response()->json(['message' => 'Password confirmed']);
    }
}
