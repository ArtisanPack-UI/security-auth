# ArtisanPack UI — Security Auth

Authentication security for Laravel: two-factor authentication (email + TOTP), password complexity rules, HaveIBeenPwned breach checking, password history, account lockout, advanced session management, and step-up authentication.

This package is part of the **ArtisanPack UI Security 2.0** split — the auth-focused features previously bundled inside `artisanpack-ui/security` (1.x) live here in 2.0+.

## Features

- **Two-factor authentication** — `TwoFactor` Facade with `EmailProvider` (default) and Google2FA-backed TOTP. Trait `TwoFactorAuthenticatable` for User models. `TwoFactorCodeMailable` for email delivery.
- **Password security** (`PasswordSecurityService`) — complexity rules, breach checking via HaveIBeenPwned, history enforcement, expiration tracking. Drop-in `Rule` classes: `PasswordComplexity`, `NotCompromised`, `PasswordHistoryRule`, `PasswordPolicy`.
- **Account lockout** (`AccountLockoutManager`) — user-level and IP-level lockouts with configurable durations, failed-attempt tracking, lockout history.
- **Advanced session management** (`AdvancedSessionManager`) — session bindings (IP / UA), concurrent session limits, session rotation, programmatic termination.
- **Middleware** — `two-factor`, `password.policy`, `check.lockout`, `step-up`.
- **Livewire components** — `PasswordStrengthMeter`, `AccountLockoutStatus`, `SessionManager`, `StepUpAuthenticationModal` with shipped Blade views (plain HTML + Tailwind, no `livewire-ui-components` dep).
- **Eloquent models** — `AccountLockout`, `PasswordHistory`, `UserSession`.
- **Migrations** — adds 2FA columns to `users`, plus tables for password history, user sessions, and account lockouts.
- **Artisan command** — `security:lockout` (manage account lockouts: list, lock, unlock, clear).
- **Events** — `AccountLocked`.

## Installation

```bash
composer require artisanpack-ui/security-auth
php artisan migrate
```

> **Note:** the bundled migrations assume the standard Laravel `users` table exists. If you're adding this package to an app without one, run Laravel's default migrations first.

(Optional) Publish the config:

```bash
php artisan vendor:publish --tag=security-auth-config
```

## Quick start

### Enable 2FA on a User model

```php
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
}
```

```php
use ArtisanPackUI\SecurityAuth\Facades\TwoFactor;

// Generate secret + recovery codes (e.g. during 2FA setup)
$user->generateTwoFactorSecret();
$user->generateRecoveryCodes();

// Verify a code (e.g. during login challenge)
if ( TwoFactor::verify( $user, $request->input('code') ) ) {
    // success
}
```

### Validate a password with full policy

```php
use ArtisanPackUI\SecurityAuth\Rules\PasswordPolicy;

$request->validate([
    'password' => ['required', 'confirmed', new PasswordPolicy],
]);
```

`PasswordPolicy` is a composite that runs complexity + breach check + history checks together. Use individual rules (`PasswordComplexity`, `NotCompromised`, `PasswordHistoryRule`) for finer control.

### Apply middleware

```php
Route::middleware('two-factor')->group(function (): void {
    // routes requiring valid 2FA
});

Route::middleware('check.lockout')->group(function (): void {
    // routes that should refuse locked accounts
});

Route::middleware('step-up')->group(function (): void {
    // routes requiring a fresh credential challenge before access
});
```

### Mount a Livewire component

```blade
<livewire:password-strength-meter wire:model="password" />
<livewire:account-lockout-status />
<livewire:session-manager />
<livewire:step-up-authentication-modal />
```

The four shipped Blade views render in plain HTML + Tailwind. Publish + override to customize.

## Documentation

- [Documentation home](docs/home.md)
- [Getting started](docs/getting-started.md)
- [Installation](docs/installation.md)
- [Usage](docs/usage.md)
- [Advanced](docs/advanced.md)
- [FAQ](docs/faq.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Changelog](CHANGELOG.md)

## Requirements

- PHP 8.2+
- Laravel 10 / 11 / 12
- `pragmarx/google2fa-laravel: ^2.3` (pulled in automatically) for TOTP 2FA

## Sibling packages

| Package | Scope |
|---|---|
| [`artisanpack-ui/security`](https://github.com/ArtisanPack-UI/security) | Core: input sanitization, escaping, CSP, security headers |
| [`artisanpack-ui/security-advanced-auth`](https://github.com/ArtisanPack-UI/security-advanced-auth) | WebAuthn, SSO, social login, biometric, device fingerprinting |
| [`artisanpack-ui/rbac`](https://github.com/ArtisanPack-UI/rbac) | Roles, permissions, Gate integration |
| [`artisanpack-ui/secure-uploads`](https://github.com/ArtisanPack-UI/secure-uploads) | File validation, malware scanning, signed-URL serving |
| [`artisanpack-ui/security-analytics`](https://github.com/ArtisanPack-UI/security-analytics) | Event logging, anomaly detection, SIEM, dashboards |
| [`artisanpack-ui/compliance`](https://github.com/ArtisanPack-UI/compliance) | GDPR / CCPA / LGPD compliance tools |

## License

MIT — see [LICENSE](LICENSE).

## Contributing

Please read the [contributing guidelines](CONTRIBUTING.md) before opening an issue or PR.
