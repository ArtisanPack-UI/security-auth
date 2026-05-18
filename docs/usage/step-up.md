---
title: Step-Up Authentication
---

# Step-Up Authentication

Forces a fresh credential challenge before access to sensitive operations — even for already-authenticated users. Common pattern: require re-confirmation before changing email, deleting account, viewing financial detail, etc.

## How it works

1. User is already signed in.
2. Request hits a route protected by `step-up` middleware.
3. Middleware checks `session()->get('step_up_verified_at')` against `freshness_minutes` config.
4. If stale or missing, middleware redirects to a challenge page (or returns 403 for API requests).
5. Challenge page mounts `StepUpAuthenticationModal` Livewire component.
6. User verifies via available method (password, 2FA, WebAuthn).
7. Component writes a fresh `step_up_verified_at` timestamp to session.
8. User is redirected back to the original route.

## Apply the middleware

```php
Route::middleware('step-up')->group(function (): void {
    Route::delete('/account', [AccountController::class, 'destroy']);
    Route::put('/email', [EmailController::class, 'update']);
    Route::get('/api-tokens', [ApiTokenController::class, 'index']);
});
```

## Tuning freshness

```php
'step_up' => [
    'freshness_minutes' => 15,
    'available_methods' => ['password', '2fa', 'webauthn'],
],
```

- Lower freshness = more re-challenges = more security, more friction.
- Restrict `available_methods` per app needs — e.g. drop `'password'` if you require 2FA for step-up.

## Triggering programmatically

Outside of the middleware path, force a step-up check inline:

```php
if ( session()->get('step_up_verified_at') < now()->subMinutes(15) ) {
    return redirect()->route('step-up.challenge', ['back' => url()->current()]);
}
```

## Using the Livewire modal

```blade
<livewire:step-up-authentication-modal />
```

Open it from JS / Alpine:

```html
<button @click="$wire.dispatch('open-step-up', { redirectUrl: '/account' })">
    Delete account
</button>
```

The modal:
- Lists available methods (password / 2FA / WebAuthn etc.) based on what the user has set up
- Verifies the credential against the same logic as login
- On success: writes `step_up_verified_at`, dispatches `step-up-verified` event, navigates to `redirectUrl` if supplied

## When to skip step-up

- Read-only sensitive views — depends on threat model. Skip if you don't want the friction; require if data exfiltration matters.
- API endpoints called by your own JS that already has fresh auth — skip; step-up is a UX pattern, less applicable headless.
- Already-elevated sessions (within freshness window) — middleware handles this automatically.
