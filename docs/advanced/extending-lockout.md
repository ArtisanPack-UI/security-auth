---
title: Extending the Lockout Manager
---

# Extending the Lockout Manager

`AccountLockoutManager` is concrete (not interface-driven) — bound under `AccountLockoutInterface` for resolution. To customize behavior, subclass and rebind.

## Common extension points

### Add notification on lockout

Override `lockUser()` to notify the user automatically:

```php
namespace App\Auth;

use ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager as BaseManager;
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Contracts\Auth\Authenticatable;

class NotifyingLockoutManager extends BaseManager
{
    public function lockUser(
        Authenticatable $user,
        string $reason,
        string $lockoutType = 'temporary',
        ?int $durationMinutes = null,
        array $metadata = [],
    ): AccountLockout {
        $lockout = parent::lockUser( $user, $reason, $lockoutType, $durationMinutes, $metadata );

        $user->notify( new \App\Notifications\AccountLocked( $lockout ) );

        return $lockout;
    }
}
```

Register your subclass:

```php
$this->app->singleton( \ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager::class, NotifyingLockoutManager::class );
$this->app->bind( \ArtisanPackUI\SecurityAuth\Authentication\Contracts\AccountLockoutInterface::class, NotifyingLockoutManager::class );
```

### Tier-based lockout durations

Vary lockout duration by user tier (premium users get shorter lockouts, etc.):

```php
class TieredLockoutManager extends BaseManager
{
    public function lockUser(
        Authenticatable $user,
        string $reason,
        string $lockoutType = 'temporary',
        ?int $durationMinutes = null,
        array $metadata = [],
    ): AccountLockout {
        $durationMinutes ??= match ( $user->tier ?? 'free' ) {
            'enterprise' => 5,
            'premium'    => 10,
            default      => 30,
        };

        return parent::lockUser( $user, $reason, $lockoutType, $durationMinutes, $metadata );
    }
}
```

### Skip lockout for trusted IPs

Override `recordFailedAttempt()` to bypass for office IPs:

```php
class TrustedIpLockoutManager extends BaseManager
{
    public function recordFailedAttempt(
        string $trigger,
        ?Authenticatable $user = null,
        ?string $ipAddress = null,
    ): array {
        if ( in_array( $ipAddress, config('security.trusted_ips', []), true ) ) {
            return ['locked' => false, 'attempts' => 0, 'remaining' => PHP_INT_MAX];
        }

        return parent::recordFailedAttempt( $trigger, $user, $ipAddress );
    }
}
```

## Conventions

- **Preserve event firing.** The base `lockUser()` dispatches `AccountLocked`. Calling `parent::lockUser()` (rather than reimplementing) keeps the event flowing to any listeners.
- **Return types must match.** The contract returns `AccountLockout` from lock methods; your overrides must too.
- **Keep manager state minimal.** The base class is mostly stateless — derived state lives in the DB rows. Your overrides should match that pattern.
