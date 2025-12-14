<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\PasswordUpdateData;
use App\Http\Controllers\Controller;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateData $data): JsonResponse
    {
        $this->userService->update(request()->user(), [
            'password' => Hash::make($data->password),
        ]);

        return response()->json(['message' => 'Password updated']);
    }
}
