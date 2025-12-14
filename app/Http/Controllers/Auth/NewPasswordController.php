<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\NewPasswordStoreData;
use App\Http\Controllers\Controller;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(NewPasswordStoreData $data): JsonResponse
    {
        $status = Password::reset(
            $data->all(),
            function ($user) use ($data) {
                $this->userService->update($user, [
                    'password' => Hash::make($data->password),
                    'remember_token' => Str::random(60),
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
