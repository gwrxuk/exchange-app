<?php

namespace App\Data\Profile;

use App\Models\User;
use Illuminate\Validation\Rule as ValidationRule;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

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
