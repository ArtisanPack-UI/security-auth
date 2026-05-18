---
title: Troubleshooting
---

# Troubleshooting

## `View [security-auth::livewire.*] not found`

This was a 0.x bug — the 4 Livewire components didn't have shipped views. Fixed in the v1.0 release. If you still see this, you're on a pre-1.0 build.

## `SQLSTATE[HY000]: General error: 1 no such table: users` when running migrations

The package's `add_two_factor_to_users_table` migration assumes a `users` table exists. Run Laravel's default migrations first. Long-term fix: tracked as [#7](https://github.com/ArtisanPack-UI/security-auth/issues/7) (add `Schema::hasTable()` guards).

## 2FA codes are always rejected

Three common causes:

1. **Clock skew (TOTP).** The TOTP code is time-based — your server clock must be within ~30s of the user's authenticator app. Sync NTP.
2. **Wrong secret column.** If you migrated from another package, ensure `two_factor_secret` is encrypted via Laravel's Encrypter (the trait expects this).
3. **Email provider cache TTL.** Codes expire after `two_factor.code_lifetime` minutes (default 5). Lengthen if your users routinely take longer.

## `pragmarx/google2fa-laravel` missing

If you see `Class 'PragmaRX\Google2FA\...' not found`, the dependency didn't install. Run `composer require pragmarx/google2fa-laravel`. The package's composer.json requires it but a `composer install` without dev deps in certain scenarios can skip it.

## Account stays locked after duration expires

`AccountLockoutManager::isUserLocked()` checks the lockout's `unlocks_at` against `now()`. If your server clock is wrong, locks don't expire as expected. Verify with `date` on the server.

For permanent lockouts (`type=permanent`), the lockout never expires automatically — unlock manually via `security:lockout unlock --user=...` or `$lockoutManager->unlockUser($user)`.

## Sessions are terminated unexpectedly

If you have `bind_to_ip => true`, any IP change (mobile network swap, VPN toggle, ISP rotation) terminates the session. Either:
- Set `bind_to_ip => false` for a friendlier UX with weaker session-hijack protection
- Keep it on for high-security apps and live with the friction
- Implement a "remember device" flow that whitelists specific IPs per user

## Password policy rejects passwords that meet documented requirements

`PasswordPolicy` is composite — check the individual rules:
- `PasswordComplexity` thresholds in config — verify they match what you advertise to users
- `NotCompromised` — HIBP may flag a password that's "complex enough" but appeared in a breach
- `PasswordHistoryRule` — recently-used passwords are blocked per `password_security.history_count`

Test each rule individually to identify which is rejecting.

## Step-up modal doesn't open

The modal listens on the `open-step-up` event. From Alpine: `@click="$wire.dispatch('open-step-up')"`. From your JS: `Livewire.dispatch('open-step-up', { redirectUrl: '...' })`. Make sure the component is mounted on the page (`<livewire:step-up-authentication-modal />`).

## Tests fail with `no such table: users`

Add this to your test setup:

```php
beforeEach( function (): void {
    if ( ! Schema::hasTable( 'users' ) ) {
        Schema::create( 'users', function ( Blueprint $table ): void {
            $table->id();
            $table->string( 'name' );
            $table->string( 'email' )->unique();
            $table->timestamp( 'email_verified_at' )->nullable();
            $table->string( 'password' );
            $table->rememberToken();
            $table->timestamps();
        } );
    }

    $this->artisan( 'migrate' );
} );
```

The package's `tests/Feature/Livewire/ComponentRenderTest.php` uses this pattern — copy from there.

## Still stuck?

Open an issue at https://github.com/ArtisanPack-UI/security-auth/issues with:

- PHP and Laravel versions
- Which subsystem (2FA, password security, lockout, sessions, step-up)
- The exact error / behavior with a minimal reproduction
- Relevant config (with any secrets redacted)
