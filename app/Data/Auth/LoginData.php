<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class LoginData extends Data
{
    public function __construct(
        #[Required, StringType, Email]
        public string $email,
        
        #[Required, StringType]
        public string $password,
        
        public bool $remember = false,
    ) {}
}

