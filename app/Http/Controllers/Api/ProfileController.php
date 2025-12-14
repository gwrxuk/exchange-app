<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\MyOrdersRequest;
use App\Repositories\Contracts\UserRepositoryInterface;

class ProfileController extends Controller
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function show(ProfileRequest $request)
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

    public function orders(MyOrdersRequest $request)
    {
        // User's orders
        return response()->json($request->user()->orders()->orderBy('created_at', 'desc')->get());
    }
}
