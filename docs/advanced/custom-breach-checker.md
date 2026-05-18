---
title: Custom Breach Checker
---

# Custom Breach Checker

`BreachCheckerInterface` is the contract — replace `HaveIBeenPwnedService` to point at a different breach data source (an internal feed, an enterprise security service, or a self-hosted HIBP clone).

```php
namespace ArtisanPackUI\SecurityAuth\Contracts;

interface BreachCheckerInterface
{
    public function check( string $password ): int;        // count of breach occurrences
    public function isCompromised( string $password ): bool;
}
```

## Example: internal blocklist + HIBP fallback

```php
namespace App\Services;

use ArtisanPackUI\SecurityAuth\Contracts\BreachCheckerInterface;
use ArtisanPackUI\SecurityAuth\Services\HaveIBeenPwnedService;

class LayeredBreachChecker implements BreachCheckerInterface
{
    public function __construct(
        protected HaveIBeenPwnedService $hibp,
        protected array $internalBlocklist,
    ) {}

    public function check( string $password ): int
    {
        // Internal blocklist hits dominate — return a high count to ensure isCompromised() is true.
        if ( in_array( $password, $this->internalBlocklist, true ) ) {
            return 1_000_000;
        }

        return $this->hibp->check( $password );
    }

    public function isCompromised( string $password ): bool
    {
        return $this->check( $password ) > 0;
    }
}
```

## Registering

```php
$this->app->bind(
    \ArtisanPackUI\SecurityAuth\Contracts\BreachCheckerInterface::class,
    fn ( $app ) => new \App\Services\LayeredBreachChecker(
        hibp: $app->make( \ArtisanPackUI\SecurityAuth\Services\HaveIBeenPwnedService::class ),
        internalBlocklist: config('security.password_blocklist', []),
    ),
);
```

`PasswordSecurityService` and `NotCompromised` both resolve `BreachCheckerInterface` from the container, so your implementation is used everywhere.

## Conventions

- **k-anonymity for any external API.** If you proxy to a third-party service, don't send the plaintext password — send a hash prefix as HIBP does. The shipped `HaveIBeenPwnedService` is the reference implementation.
- **Cache results.** Even fast lookups add latency; cache by hash for a few hours to absorb password-set bursts.
- **Fail open vs closed.** When the upstream is down, decide deliberately: return 0 (fail open — let the password through with a logged warning) vs throw (fail closed — block the set). Most apps want fail-open with monitoring on the failure rate.
