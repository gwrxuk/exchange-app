<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\CurrentPassword;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Password;

class PasswordUpdateData extends Data
{
    public function __construct(
        #[Required, CurrentPassword]
        public string $current_password,

        #[Required, Confirmed, Password(min: 8)]
        public string $password,
    ) {}
}

