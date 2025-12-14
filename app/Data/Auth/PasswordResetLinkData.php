<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class PasswordResetLinkData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
    ) {}
}

