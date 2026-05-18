---
title: Artisan Commands
---

# Artisan Commands

One shipped command: `security:lockout`. Handles all lockout management subactions.

## `security:lockout list`

```bash
php artisan security:lockout list
php artisan security:lockout list --user=user@example.com
php artisan security:lockout list --ip=198.51.100.1
php artisan security:lockout list --active
php artisan security:lockout list --since=2026-05-01
```

Lists lockouts matching the filters. By default shows recent lockouts (active + cleared). `--active` shows only currently-active ones.

## `security:lockout lock`

```bash
php artisan security:lockout lock --user=user@example.com --duration=60 --reason="Suspicious login pattern"
php artisan security:lockout lock --ip=198.51.100.1 --duration=240 --reason="Credential stuffing"
php artisan security:lockout lock --user=42 --type=permanent --reason="Account banned"
```

Applies a manual lockout. `--duration` in minutes (ignored for `type=permanent`). `--type` is `temporary` (default) or `permanent`.

## `security:lockout unlock`

```bash
php artisan security:lockout unlock --user=user@example.com
php artisan security:lockout unlock --ip=198.51.100.1
```

Clears the active lockout. Subsequent failed attempts can re-trigger automatic locks per policy.

## `security:lockout clear-attempts`

```bash
php artisan security:lockout clear-attempts --user=user@example.com
php artisan security:lockout clear-attempts --ip=198.51.100.1
```

Resets the failed-attempt counter without unlocking. Useful for clearing legitimate failed attempts (typos, password manager mismatch) without giving up the lockout protection.

## Exit codes

- `0` on success
- `1` on user input error (unknown user / IP, conflicting flags)
- `2` on system error (DB issue, etc.)
