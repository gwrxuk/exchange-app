<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\NewPasswordStoreData;
use App\Http\Controllers\Controller;
use App\Http\Requests\NewPasswordCreateRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Display the password reset view.
     */
    public function create(NewPasswordCreateRequest $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
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
                $this->users->update($user, [
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
