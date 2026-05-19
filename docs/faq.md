---
title: FAQ
---

# FAQ

## Does this package require `artisanpack-ui/security`?

No. Security Auth is standalone — only `artisanpack-ui/core` and `pragmarx/google2fa-laravel` get pulled in beyond standard Laravel.

## Can I use TOTP without sending email?

Yes. Set `two_factor.default_provider => 'google2fa'`. The email provider stays available — switch per-call with `TwoFactor::provider('email')->sendChallenge( $user )`. Or use `TwoFactor::provider('google2fa')`.

## Does `NotCompromised` send my users' passwords to HaveIBeenPwned?

No — it uses k-anonymity. Only the first 5 chars of the SHA-1 hash leave your server; HIBP returns all hashes starting with that prefix, and your server scans the response locally. The plaintext password never crosses the network.

## What's the behavior when the HIBP API is unreachable?

The shipped service fails open — returns 0 (not compromised) and logs a warning. This avoids blocking password sets during transient outages. To fail closed instead, subclass `HaveIBeenPwnedService::check()` to throw on connection failure and let validation reject the set.

## Can I use this with Laravel Sanctum / Passport?

Yes. The 2FA flow assumes a session for the challenge UX, but once verified the user's tokens work as normal. For pure API auth (no session), implement 2FA at the token-issue step rather than per-request.

## What happens to active sessions when an account is locked?

Existing sessions stay active by default. To force log-out on lock, listen for `AccountLocked` and call `$sessionManager->terminateAllSessions( $event->user )` from your listener.

## Does account lockout protect against credential stuffing across many accounts?

User-level lockout doesn't — `lockout_ip => true` adds an IP-level layer that catches the same IP hitting many usernames. For more sophisticated patterns (rotating IPs, distributed attacks), pair with `artisanpack-ui/security-analytics`'s `CredentialStuffingDetector`.

## Can I use the package without Livewire?

Yes. The 4 Livewire components are optional — the service surface (services, traits, rules, middleware, commands, events) works without Livewire installed. Build your own UI on top of the same APIs.

## How do I migrate users from a different 2FA package?

Two scenarios:

- **From Laravel Fortify.** Fortify's `two_factor_secret` and `two_factor_recovery_codes` columns are compatible with this package — the trait reads/writes the same columns. After installing, existing Fortify users have working 2FA immediately.
- **From a custom implementation.** Copy your existing secret / recovery code columns into the package's expected shape (TEXT, encrypted via Laravel's Encrypter), then `$user->two_factor_enabled_at = now()` for users who had 2FA on.

## Why do the migrations require an existing `users` table?

The `add_two_factor_to_users_table` migration uses `Schema::table('users', ...)` which assumes the table exists. Run Laravel's default migrations first. For long-term safety, the migration should add a `Schema::hasTable('users')` guard — tracked as issue [#7](https://github.com/ArtisanPack-UI/security-auth/issues/7).

## How long should I set `freshness_minutes` for step-up?

Depends on the operation's sensitivity:
- 5 minutes — destructive actions (delete account, change email, view API tokens)
- 15 minutes — moderately sensitive (change payment method, export data)
- 60 minutes — light sensitivity (rename account, change profile photo)

There's no universal answer — match the friction tolerance of your users against the threat model of the action.
