<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewPasswordCreateRequest;
use App\Http\Requests\NewPasswordStoreRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
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
    public function store(NewPasswordStoreRequest $request): RedirectResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // We use Repository here if possible, but Password::reset expects a callback with a User model.
                // Since EloquentUserRepository returns Eloquent Models, this is fine.
                // We can use $this->users->update() but forceFill is specific to Eloquent.
                // Let's stick to repository update for consistency if possible, 
                // but for password reset with token logic, we might need to be careful.
                
                // $user->forceFill([...])->save() is standard Laravel behavior inside this callback.
                // Replacing it with repo call:
                
                $this->users->update($user, [
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ]);

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
