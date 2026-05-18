---
title: Middleware
---

# Middleware

Four middleware aliases register with Laravel's router. Apply per-route or in middleware groups.

## `two-factor`

Requires the authenticated user to have a verified 2FA session. Redirects to the 2FA challenge route when missing.

```php
Route::middleware(['auth', 'two-factor'])->group(function (): void {
    // Routes requiring 2FA verification this session
});
```

If the user hasn't enabled 2FA at all, the middleware allows the request through (configurable — set `two_factor.require_enabled => true` to force enrollment first).

## `password.policy`

Refuses access when the user's password is expired or doesn't meet current policy. Redirects to a password change flow.

```php
Route::middleware(['auth', 'password.policy'])->group(function (): void {
    // Routes that require an up-to-date password
});
```

Useful for enforcing rotation: set `password_security.expire_after_days => 90` and apply this middleware to your sensitive routes.

## `check.lockout`

Aborts requests from locked users (403) or locked IPs (429).

```php
Route::middleware('check.lockout')->group(function (): void {
    // Or apply to all routes via your kernel/middleware-group
});
```

Often applied globally to the `web` group so even unauthenticated routes (e.g. login page) refuse locked IPs.

## `step-up`

Requires a fresh credential challenge within `freshness_minutes`. See [Step-up authentication](step-up.md).

```php
Route::middleware('step-up')->group(function (): void {
    Route::delete('/account', [AccountController::class, 'destroy']);
});
```

## Ordering

Combine in this order when stacking:

```php
Route::middleware(['auth', 'check.lockout', 'password.policy', 'two-factor', 'step-up'])
    ->group(function (): void {
        // ...
    });
```

- `auth` first — establishes the user.
- `check.lockout` second — refuse locked before doing more work.
- `password.policy` third — refuse stale passwords before honoring 2FA / step-up.
- `two-factor` fourth — verified 2FA for the session.
- `step-up` last — most expensive (interactive challenge) — only when everything else passed.
