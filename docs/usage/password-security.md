---
title: Password Security
---

# Password Security

`PasswordSecurityService` (bound to `PasswordSecurityServiceInterface`) is the orchestrator. Four validation rules wrap individual concerns:

- `PasswordComplexity` — length, uppercase, lowercase, numbers, symbols
- `NotCompromised` — HaveIBeenPwned breach check
- `PasswordHistoryRule` — disallow reuse of previous N passwords
- `PasswordPolicy` — composite of all three plus expiration check

## Use the composite rule

The simplest path — all checks in one rule:

```php
use ArtisanPackUI\SecurityAuth\Rules\PasswordPolicy;

$request->validate([
    'password' => ['required', 'confirmed', new PasswordPolicy],
]);
```

## Use individual rules for finer control

```php
use ArtisanPackUI\SecurityAuth\Rules\PasswordComplexity;
use ArtisanPackUI\SecurityAuth\Rules\NotCompromised;
use ArtisanPackUI\SecurityAuth\Rules\PasswordHistoryRule;

$request->validate([
    'password' => [
        'required',
        'confirmed',
        new PasswordComplexity,
        new NotCompromised,
        new PasswordHistoryRule( $user ),
    ],
]);
```

## Recording a new password

After successful update, record it in history so future changes can't reuse it:

```php
use ArtisanPackUI\SecurityAuth\Contracts\PasswordSecurityServiceInterface;

$user->password = Hash::make( $request->input('password') );
$user->save();

app( PasswordSecurityServiceInterface::class )
    ->recordPassword( $user->password, $user );
```

The service writes a `PasswordHistory` row. The `PasswordHistoryRule` checks against the configured `history_count` previous entries.

## Checking against HaveIBeenPwned without writing

`HaveIBeenPwnedService` uses k-anonymity — only the first 5 chars of the SHA-1 hash leave your server.

```php
use ArtisanPackUI\SecurityAuth\Services\HaveIBeenPwnedService;

$service = app( HaveIBeenPwnedService::class );
$compromisedCount = $service->check( $password );   // 0 = not seen, >0 = times seen in breaches
if ( $service->isCompromised( $password ) ) {
    // refuse
}
```

## Expiration

`PasswordSecurityService::isExpired($user)` and `daysUntilExpiration($user)` check against the configured `expire_after_days`. Combine with the `password.policy` middleware to force a reset when expired:

```php
Route::middleware(['auth', 'password.policy'])->group(function (): void {
    // Routes here redirect to a "change your password" flow when
    // the user's password is expired or doesn't meet current policy.
});
```

## Disabling individual checks

Configure thresholds to 0 / false to disable a specific check:

```php
'password_security' => [
    'breach_check'      => false,   // skip HIBP
    'history_count'     => 0,       // allow reuse
    'expire_after_days' => 0,       // never expire
],
```

Even with checks disabled the rules don't throw — they simply pass.
