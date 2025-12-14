<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Password;

class RegisterData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        
        #[Required, StringType, Email, Max(255), Unique('users', 'email')]
        public string $email,
        
        #[Required, Confirmed, Password(min: 8)]
        public string $password,
    ) {}
}

