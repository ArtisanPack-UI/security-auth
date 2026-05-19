---
title: ArtisanPack UI Security Auth Documentation
---

# ArtisanPack UI Security Auth

Authentication security for Laravel: two-factor auth (email + TOTP), password complexity and breach checking, account lockout, advanced session management, and step-up authentication.

This package is part of the **ArtisanPack UI Security 2.0** split.

## What's in this package

- **Two-factor authentication** — `TwoFactor` Facade, email + TOTP providers, recovery codes, trait for User models
- **Password security** — complexity rules, HaveIBeenPwned breach checks, history enforcement, expiration tracking
- **Account lockout** — user + IP-level lockouts with configurable durations
- **Session management** — bindings, rotation, concurrent limits, programmatic termination
- **Step-up authentication** — fresh credential challenge for sensitive operations
- **4 Livewire components** with shipped Blade views

## Documentation map

- [Getting Started](getting-started.md) — 5-minute path
- [Installation](installation.md)
- [Usage](usage.md) — per-subsystem reference
- [Advanced](advanced.md) — extending providers, custom rules
- [FAQ](faq.md)
- [Troubleshooting](troubleshooting.md)

## Related packages

| Package | Scope |
|---|---|
| [`artisanpack-ui/security`](https://github.com/ArtisanPack-UI/security) | Core: sanitization, escaping, CSP, security headers |
| [`artisanpack-ui/security-advanced-auth`](https://github.com/ArtisanPack-UI/security-advanced-auth) | WebAuthn, SSO, social login |
| [`artisanpack-ui/rbac`](https://github.com/ArtisanPack-UI/rbac) | Roles, permissions, Gate integration |
| [`artisanpack-ui/secure-uploads`](https://github.com/ArtisanPack-UI/secure-uploads) | File validation, malware scanning |
| [`artisanpack-ui/security-analytics`](https://github.com/ArtisanPack-UI/security-analytics) | Event logging, anomaly detection, SIEM, dashboards |
