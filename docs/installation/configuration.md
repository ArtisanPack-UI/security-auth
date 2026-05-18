---
title: Configuration
---

# Configuration

Publish the shipped config:

```bash
php artisan vendor:publish --tag=security-auth-config
```

Lives at `config/artisanpack/security-auth.php`. Key sections:

## `two_factor`

```php
'two_factor' => [
    'enabled'         => env('SECURITY_AUTH_2FA_ENABLED', true),
    'default_provider' => env('SECURITY_AUTH_2FA_PROVIDER', 'email'),  // email | google2fa
    'code_lifetime'    => 5,    // minutes — for email provider
    'recovery_codes'   => [
        'count'  => 8,
        'length' => 10,
    ],
],
```

Switch `default_provider` to `google2fa` for TOTP (Google Authenticator, Authy, 1Password, etc.). Email provider is simpler to deploy but less secure.

## `password_security`

```php
'password_security' => [
    'min_length'         => 12,
    'require_uppercase'  => true,
    'require_lowercase'  => true,
    'require_numbers'    => true,
    'require_symbols'    => true,
    'history_count'      => 5,         // previous N passwords blocked
    'breach_check'       => true,      // HIBP lookup on every set
    'expire_after_days'  => 90,        // 0 to disable
],
```

## `account_lockout`

```php
'account_lockout' => [
    'enabled'             => true,
    'max_attempts'        => 5,
    'lockout_minutes'     => 15,
    'attempts_window'     => 5,        // minutes
    'lockout_ip'          => true,     // also lock the IP, not just the user
    'ip_lockout_minutes'  => 60,
],
```

## `sessions`

```php
'sessions' => [
    'enabled'                  => true,
    'bind_to_ip'               => false,   // strict — terminate on IP change
    'bind_to_user_agent'       => false,   // strict — terminate on UA change
    'max_concurrent'           => 0,        // 0 = no limit
    'rotate_on_privilege_change' => true,
    'idle_timeout_minutes'     => 60,
],
```

## `step_up`

```php
'step_up' => [
    'enabled'                 => true,
    'freshness_minutes'       => 15,    // re-challenge after this idle period
    'available_methods'       => ['password', '2fa'],  // restrict per app needs
],
```
