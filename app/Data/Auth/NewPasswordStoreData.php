<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class NewPasswordStoreData extends Data
{
    public function __construct(
        #[Required]
        public string $token,

        #[Required, Email]
        public string $email,

        #[Required, Confirmed, Password(min: 8)]
        public string $password,
    ) {}
}
