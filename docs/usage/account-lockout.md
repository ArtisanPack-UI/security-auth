---
title: Account Lockout
---

# Account Lockout

`AccountLockoutManager` (bound to `AccountLockoutInterface`) handles both user-level and IP-level lockouts driven by failed-attempt counts.

## Recording a failed attempt

Trigger from your login flow:

```php
use ArtisanPackUI\SecurityAuth\Authentication\Contracts\AccountLockoutInterface;

$lockoutManager = app( AccountLockoutInterface::class );

// On failed login
$result = $lockoutManager->recordFailedAttempt(
    trigger: 'login',
    user: $user,                   // optional — pass null for anonymous attempts
    ipAddress: $request->ip(),
);

// $result contains: ['locked' => bool, 'attempts' => int, 'remaining' => int]
if ( $result['locked'] ) {
    // user just hit the lockout threshold — refuse login
}
```

If the attempt threshold is hit within the configured window, `recordFailedAttempt` automatically locks the user (and the IP if `lockout_ip` is on).

## Clearing failed attempts

On successful login, clear the counter:

```php
$lockoutManager->clearFailedAttempts(
    user: $user,
    ipAddress: $request->ip(),
);
```

## Manual locks

```php
$lockoutManager->lockUser(
    user: $user,
    reason: 'Suspicious activity detected',
    lockoutType: 'temporary',         // temporary | permanent
    durationMinutes: 60,
    metadata: ['source' => 'admin'],
);

$lockoutManager->lockIp(
    ipAddress: $ip,
    durationMinutes: 240,
    reason: 'Credential stuffing detected',
);
```

## Checking lock state

```php
if ( $lockoutManager->isUserLocked( $user ) ) {
    abort(403, 'Account locked');
}

if ( $lockoutManager->isIpLocked( $request->ip() ) ) {
    abort(429, 'Too many failed attempts from this IP');
}

$activeLockout = $lockoutManager->getUserLockout( $user );  // ?AccountLockout
```

## Middleware

`check.lockout` aborts 403 for locked users and 429 for locked IPs:

```php
Route::middleware(['auth', 'check.lockout'])->group(function (): void {
    // gated routes
});
```

## Lockout history

Each lock writes an `AccountLockout` row. Query it directly:

```php
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;

AccountLockout::where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->paginate();
```

The `AccountLockoutStatus` Livewire component surfaces this on the user's account page.

## CLI

```bash
php artisan security:lockout list
php artisan security:lockout list --user=user@example.com
php artisan security:lockout lock --user=user@example.com --duration=60 --reason="..."
php artisan security:lockout unlock --user=user@example.com
php artisan security:lockout clear-attempts --user=user@example.com
```

## Events

`AccountLocked` fires when a lock is applied. Subscribe to alert / notify:

```php
use ArtisanPackUI\SecurityAuth\Events\AccountLocked;

Event::listen( AccountLocked::class, function ( AccountLocked $event ): void {
    Notification::send( $event->user, new AccountLockedNotification( $event->lockout ) );
} );
```
