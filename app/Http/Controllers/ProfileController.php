<?php

namespace App\Http\Controllers;

use App\Data\Profile\ProfileUpdateData;
use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileEditRequest;
use App\Http\Requests\ProfileDestroyRequest;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display the user's profile form.
     */
    public function show(ProfileEditRequest $request): JsonResponse
    {
        return response()->json([
            'user' => UserData::from($request->user()),
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateData $data): JsonResponse
    {
        $user = request()->user();
        $user->fill($data->all());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $this->userService->update($user, $user->getAttributes());

        return response()->json([
            'message' => 'Profile updated',
            'user' => UserData::from($user),
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(ProfileDestroyRequest $request): JsonResponse
    {
        $user = $request->user();

        Auth::logout();

        $this->userService->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Account deleted']);
    }
}
