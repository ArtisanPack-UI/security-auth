---
title: Livewire Components
---

# Livewire Components

Four components ship with Blade views in plain HTML + Tailwind. Each is independently usable.

## `<livewire:password-strength-meter />`

Real-time password strength feedback. Bind to the host form's password input:

```blade
<form>
    <input type="password" wire:model.live="password" />
    <livewire:password-strength-meter wire:model.live="password" />
    <button type="submit">Save</button>
</form>
```

Public properties:
- `password` (string) — the password being evaluated
- `userInputs` (array) — user identifiers to penalize as predictable (e.g. `[$user->name, $user->email]`)
- `score` (int 0-100)
- `label` (string — "Weak", "Fair", "Good", "Strong")
- `crackTime` (string — human-readable estimate)
- `feedback` (array of guidance messages)
- `requirements` (array of policy checks with `met` flag)

## `<livewire:account-lockout-status />`

Shows the user's current lockout state (if any) plus paginated history.

```blade
<livewire:account-lockout-status />
```

Use on the user's account settings page or security page so they can see why they were locked previously.

## `<livewire:session-manager />`

Lists active sessions with terminate controls.

```blade
<livewire:session-manager />
```

Shows: device, IP, location, last activity, auth method. Each non-current session has a "Sign out" button (with confirmation). A "Sign out of all other sessions" button at the top terminates everything except the current.

## `<livewire:step-up-authentication-modal />`

Renders only when opened via JavaScript / Alpine:

```blade
<livewire:step-up-authentication-modal />

<button @click="$wire.dispatch('open-step-up', { redirectUrl: '/account/delete' })">
    Delete account
</button>
```

Listens on the `open-step-up` event. On successful verification, dispatches `step-up-verified` and navigates to the supplied `redirectUrl`.

## Customizing views

The shipped Blade views are plain HTML + Tailwind. To customize:

1. Publish them with the `security-auth-views` tag:
   ```bash
   php artisan vendor:publish --tag=security-auth-views
   ```
2. Or shadow them by placing files at `resources/views/vendor/security-auth/livewire/*.blade.php` — Laravel resolves overrides before package defaults.

Common customizations:
- Wrap in your own design-system components (`<x-card>`, `<x-button>`, etc.)
- Swap the strength bar for a more elaborate visualization
- Replace the inline event-detail panel with a modal
- Customize copy / translations
