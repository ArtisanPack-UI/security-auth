---
title: Requirements
---

# Requirements

## PHP

- PHP 8.2+

## Laravel

- Laravel 10 / 11 / 12 / 13

## Composer dependencies (pulled in automatically)

- `artisanpack-ui/core: ^1.0`
- `pragmarx/google2fa-laravel: ^2.3 | ^3.0` — backs the TOTP 2FA provider (v3.x is the Laravel-13-compatible line)

## Optional dependencies

- `livewire/livewire: ^3.6 | ^4.0` — only required for the 4 Livewire components. The rest of the package (services, traits, middleware, rules) works without Livewire.

## Database

Any Eloquent-supported driver. The migrations alter the `users` table and create three new tables (`password_history`, `user_sessions`, `account_lockouts`).

**Prerequisite**: a standard Laravel `users` table must exist before these migrations run.

## Authentication system

Designed against Laravel's standard auth setup — session-based login via `auth` middleware, user model implementing `Authenticatable`. Sanctum / Passport tokens are compatible but the 2FA challenge flow expects a session.
