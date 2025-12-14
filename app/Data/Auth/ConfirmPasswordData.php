<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\CurrentPassword;

class ConfirmPasswordData extends Data
{
    public function __construct(
        #[Required, CurrentPassword]
        public string $password,
    ) {}
}

