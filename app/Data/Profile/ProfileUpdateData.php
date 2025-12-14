<?php

namespace App\Data\Profile;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Illuminate\Validation\Rule as ValidationRule;
use App\Models\User;

class ProfileUpdateData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, StringType, Email, Max(255)]
        public string $email,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                ValidationRule::unique(User::class)->ignore(request()->user()->id),
            ],
        ];
    }
}

