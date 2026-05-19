---
title: Installation
---

# Installation

## Install via Composer

```bash
composer require artisanpack-ui/security-auth
```

Auto-registers via Laravel's package discovery.

## Run migrations

```bash
php artisan migrate
```

Three migration groups:
- `add_two_factor_to_users_table` — adds `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_enabled_at` columns to the existing `users` table
- `password/` — adds password security columns to `users` + creates `password_history`
- `authentication/` — creates `user_sessions` and `account_lockouts`

> **Prerequisite**: a standard Laravel `users` table must exist before these migrations run. Run Laravel's default migrations first.

## (Optional) Publish the config

```bash
php artisan vendor:publish --tag=security-auth-config
```

Lives at `config/artisanpack/security-auth.php`. Override 2FA provider, password policy thresholds, lockout durations, session bindings here.

## Add the trait

```php
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
}
```

## Deeper topics

- [Requirements](installation/requirements.md)
- [Configuration](installation/configuration.md)
