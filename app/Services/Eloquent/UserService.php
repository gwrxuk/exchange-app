<?php

namespace App\Services\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\UserServiceInterface;

class UserService implements UserServiceInterface
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function create(array $data): User
    {
        return $this->users->create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $this->users->update($user, $data);
    }

    public function delete(User $user): bool
    {
        return $this->users->delete($user);
    }

    public function find(int $id): ?User
    {
        return $this->users->find($id);
    }
}
