<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\PasswordUpdateData;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateData $data): JsonResponse
    {
        $this->users->update(request()->user(), [
            'password' => Hash::make($data->password),
        ]);

        return response()->json(['message' => 'Password updated']);
    }
}
