---
title: Two-Factor Authentication
---

# Two-Factor Authentication

`TwoFactorManager` (resolved via the `TwoFactor` Facade) supports two providers out of the box:

- `EmailProvider` — emails a numeric code; simpler deploy, less secure
- `Google2faProvider` — TOTP via `pragmarx/google2fa`; better security, requires authenticator app

Pick the default via `config('artisanpack.security-auth.two_factor.default_provider')`. Switch per-call to use the other.

## Enable on a User model

```php
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
}
```

This adds three columns (via migration) and four methods:
- `getTwoFactorEnabledAttribute(): bool`
- `hasTwoFactorEnabled(): bool`
- `generateTwoFactorSecret(): void`
- `generateRecoveryCodes(): void`

## Generating secrets and recovery codes

```php
$user->generateTwoFactorSecret();        // writes encrypted secret to two_factor_secret column
$user->generateRecoveryCodes();          // writes encrypted JSON array of codes
$user->two_factor_enabled_at = now();
$user->save();
```

Recovery codes display once — the user must store them somewhere safe. Re-generating invalidates all prior codes.

## Sending a challenge

```php
use ArtisanPackUI\SecurityAuth\Facades\TwoFactor;

TwoFactor::sendChallenge( $user );
// For email provider: emails a code (lifetime configurable)
// For TOTP: no-op — user reads their authenticator app
```

## Verifying

```php
if ( TwoFactor::verify( $user, $request->input('code') ) ) {
    // valid — complete login or step-up
    session()->put('two_factor_verified_at', now());
}
```

`verify()` accepts both:
- The current time-window code (email or TOTP)
- One of the user's recovery codes (consumed on use — single-use)

## Using a specific provider for one call

```php
TwoFactor::provider('google2fa')->verify( $user, $code );
```

## TOTP setup (Google Authenticator etc.)

```php
$secret = $user->two_factor_secret;
$qrCode = TwoFactor::provider('google2fa')->generateQrCode( $user );
// Display $qrCode to the user — they scan with their authenticator app
```

## Disabling 2FA

```php
$user->two_factor_secret = null;
$user->two_factor_recovery_codes = null;
$user->two_factor_enabled_at = null;
$user->save();
```

The trait doesn't provide a `disableTwoFactor()` helper because most apps need to gate this behind extra verification (re-confirm password, send confirmation email, etc.) — do that yourself.
