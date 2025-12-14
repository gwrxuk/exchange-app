<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function show(Request $request)
    {
        // Ideally we should use $this->users->find($id) but we have the authenticated user model already.
        // However, to strictly follow repo pattern for data access, we might want to reload it or just use it.
        // The user() method returns an authenticatable, which is a model.
        // Loading relationships via repo is cleaner.
        
        // $user = $request->user()->load('assets');
        // Let's assume repo should handle loading assets? Or just load on model.
        // Repos usually return models.
        
        $user = $request->user()->load('assets');
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
            ],
            'assets' => $user->assets,
        ]);
    }

    public function orders(Request $request)
    {
        // User's orders
        return response()->json($request->user()->orders()->orderBy('created_at', 'desc')->get());
    }
}
