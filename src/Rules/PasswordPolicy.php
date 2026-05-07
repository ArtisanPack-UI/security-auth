<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordPolicy implements Rule
{
    /**
     * The validation errors.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     */
    public function passes( $attribute, mixed $value ): bool
    {
        $validator = Validator::make( ['password' => $value], [
            'password' => [
                'required',
                Password::min( 8 )
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                // Delegate breach checking to the package's NotCompromised rule
                // so config flags + outage protection in HaveIBeenPwnedService
                // are honored (vs. Password::uncompromised() which bypasses them).
                new NotCompromised(),
            ],
        ] );

        if ( $validator->fails() ) {
            $this->errors = $validator->errors()->all();

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return array<int, string>
     */
    public function message(): array
    {
        return $this->errors;
    }
}
