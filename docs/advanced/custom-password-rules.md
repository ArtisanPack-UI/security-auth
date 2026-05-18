---
title: Custom Password Rules
---

# Custom Password Rules

For checks outside the shipped policy, write a normal Laravel rule:

```php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoCompanyName implements ValidationRule
{
    public function validate( string $attribute, mixed $value, Closure $fail ): void
    {
        $companyName = config('app.name');

        if ( stripos( $value, $companyName ) !== false ) {
            $fail( "The password may not contain the company name." );
        }
    }
}
```

Drop it alongside the package's rules:

```php
'password' => [
    'required',
    'confirmed',
    new PasswordPolicy,
    new \App\Rules\NoCompanyName,
],
```

## Adding to the composite

To make a custom rule run as part of `PasswordPolicy` automatically, override the binding:

```php
namespace App\Rules;

use ArtisanPackUI\SecurityAuth\Rules\PasswordPolicy as BasePolicy;
use Closure;

class CompanyPasswordPolicy extends BasePolicy
{
    public function validate( string $attribute, mixed $value, Closure $fail ): void
    {
        parent::validate( $attribute, $value, $fail );
        ( new NoCompanyName )->validate( $attribute, $value, $fail );
    }
}
```

```php
$request->validate(['password' => ['required', new CompanyPasswordPolicy]]);
```

## Extending complexity checks

`PasswordComplexity` reads its thresholds from config. To add a *new* dimension (e.g. "must contain a vowel"), subclass it:

```php
namespace App\Rules;

use ArtisanPackUI\SecurityAuth\Rules\PasswordComplexity as BaseComplexity;
use Closure;

class StrictPasswordComplexity extends BaseComplexity
{
    public function validate( string $attribute, mixed $value, Closure $fail ): void
    {
        parent::validate( $attribute, $value, $fail );

        if ( ! preg_match( '/[aeiou]/i', $value ) ) {
            $fail( 'The password must contain at least one vowel.' );
        }
    }
}
```
