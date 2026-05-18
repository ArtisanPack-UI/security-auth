---
title: Session Management
---

# Session Management

`AdvancedSessionManager` (bound to `SessionSecurityInterface`) layers session security features on top of Laravel's standard session handling.

## Recording a session at login

```php
use ArtisanPackUI\SecurityAuth\Authentication\Contracts\SessionSecurityInterface;

$sessionManager = app( SessionSecurityInterface::class );

$session = $sessionManager->createSession(
    user: $user,
    request: $request,
    authMethod: 'password',          // password | 2fa | webauthn | sso etc.
    metadata: ['location' => $city],
);
```

Writes a `UserSession` row tied to the current Laravel session ID. The row carries the IP, UA, auth method, and metadata.

## Validating bindings on each request

When `bind_to_ip` or `bind_to_user_agent` is on, run validation on every request (e.g. via your own middleware):

```php
$result = $sessionManager->validateSessionBindings( $session, $request );

if ( ! $result['valid'] ) {
    // IP or UA changed — terminate the session
    auth()->logout();
    redirect()->route('login')->with('error', 'Session invalidated for security.');
}
```

## Touching the session

Updates `last_activity_at` so the dashboard shows accurate "last active" data:

```php
$sessionManager->touchSession( $session );
```

Wire this into a middleware so it fires on every request, or call manually from significant events.

## Terminating

```php
$sessionManager->terminateSession( $sessionId );           // one
$sessionManager->terminateOtherSessions( $user, $currentSessionId );  // all except current
$sessionManager->terminateAllSessions( $user );            // sign out everywhere
```

`terminateSession` removes both the `UserSession` row and invalidates the Laravel session cookie / token via the session store.

## Listing the user's sessions

```php
$sessions = $sessionManager->getUserSessions( $user );   // Collection<UserSession>
```

The `SessionManager` Livewire component renders this list with terminate controls.

## Rotation

`rotateSession` generates a new session ID for the current session while preserving the user's auth state. Use after privilege changes (role grant, password change, etc.):

```php
$rotatedSession = $sessionManager->rotateSession( $session );
```

`session.rotate_on_privilege_change` config flag (default true) makes the session rotation happen automatically when the package detects privilege changes via events.

## Concurrent session limits

```php
'sessions' => [
    'max_concurrent' => 5,  // 0 = unlimited
],
```

When set, creating a new session past the limit terminates the oldest one first. Useful for shared accounts or to enforce "one device per user" policies.

## Idle timeout

```php
'sessions' => [
    'idle_timeout_minutes' => 60,
],
```

Sessions inactive longer than `idle_timeout_minutes` are terminated automatically the next time they're touched. The user is logged out cleanly.
