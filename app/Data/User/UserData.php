<?php

namespace App\Data\User;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public float $balance = 0.0,
    ) {}
}

