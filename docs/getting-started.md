---
title: Getting Started
---

# Getting Started

Five minutes from install to a working 2FA + password policy + lockout pipeline.

## 1. Install

```bash
composer require artisanpack-ui/security-auth
php artisan migrate
```

> The migrations add columns to the `users` table. If you don't have a standard Laravel `users` table, run Laravel's default migrations first.

## 2. Add the 2FA trait to your User model

```php
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
}
```

## 3. Enable 2FA for a user

```php
$user->generateTwoFactorSecret();   // store secret + recovery codes on the user row
$user->generateRecoveryCodes();
```

Show the user their secret as a QR code or recovery codes (one-time display only — re-generation invalidates prior codes).

## 4. Verify 2FA at login

```php
use ArtisanPackUI\SecurityAuth\Facades\TwoFactor;

if ( TwoFactor::verify( $user, $request->input('code') ) ) {
    // success — complete login
}
```

## 5. Apply the password policy

```php
use ArtisanPackUI\SecurityAuth\Rules\PasswordPolicy;

$request->validate([
    'password' => ['required', 'confirmed', new PasswordPolicy],
]);
```

`PasswordPolicy` enforces complexity + breach check + history all in one rule.

## 6. Gate routes with middleware

```php
Route::middleware(['auth', 'two-factor', 'check.lockout'])->group(function (): void {
    // protected routes
});

Route::middleware(['auth', 'password.policy'])->group(function (): void {
    // refuse access until the user's password meets current policy
});

Route::middleware('step-up')->group(function (): void {
    // require a fresh credential before access
});
```

## 7. Mount Livewire components for the user-facing surface

```blade
<livewire:password-strength-meter wire:model.live="password" />
<livewire:account-lockout-status />
<livewire:session-manager />
<livewire:step-up-authentication-modal />
```

## Next steps

- [Usage](usage.md) — per-subsystem reference
- [Advanced](advanced.md) — extending providers, custom rules
- [Installation](installation.md) — full config reference
