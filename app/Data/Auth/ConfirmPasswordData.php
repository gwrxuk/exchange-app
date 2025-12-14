<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\CurrentPassword;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ConfirmPasswordData extends Data
{
    public function __construct(
        #[Required, CurrentPassword]
        public string $password,
    ) {}
}
