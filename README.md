# ArtisanPack UI — Security Auth

Authentication security for Laravel: two-factor authentication (email/TOTP), password complexity and breach checking, account lockout, and session management.

This package is part of the **ArtisanPack UI Security 2.0** split — the auth-focused features previously bundled inside `artisanpack-ui/security` (1.x) live here in 2.0+.

> **Status:** initial extraction. Source files are migrated from `artisanpack-ui/security` 1.x. Comprehensive test coverage migration is a follow-up — see open issues.

## Installation

```bash
composer require artisanpack-ui/security-auth
```

## Scope

Once content extraction lands, this package will provide:

- Two-factor authentication (`TwoFactor` facade, email + TOTP providers)
- Password security (complexity rules, history, HaveIBeenPwned breach checks)
- Account lockout management
- Advanced session management
- Livewire components: `PasswordStrengthMeter`, `AccountLockoutStatus`, `SessionManager`, `StepUpAuthenticationModal`
- Middleware: `TwoFactorMiddleware`, `CheckAccountLockout`

## Sibling packages

| Package | Scope |
|---|---|
| [`artisanpack-ui/security`](https://github.com/ArtisanPack-UI/security) | Core: input sanitization, output escaping, KSES, CSP, security headers |
| [`artisanpack-ui/security-advanced-auth`](https://github.com/ArtisanPack-UI/security-advanced-auth) | WebAuthn, SSO, social login, biometric, device fingerprinting |
| [`artisanpack-ui/rbac`](https://github.com/ArtisanPack-UI/rbac) | Roles, permissions, hierarchy, Blade directives, Gate integration |
| [`artisanpack-ui/secure-uploads`](https://github.com/ArtisanPack-UI/secure-uploads) | File validation, malware scanning, secure storage |
| [`artisanpack-ui/security-analytics`](https://github.com/ArtisanPack-UI/security-analytics) | Event logging, anomaly detection, SIEM, dashboards |
| [`artisanpack-ui/compliance`](https://github.com/ArtisanPack-UI/compliance) | GDPR / CCPA / LGPD compliance tools |
| [`artisanpack-ui/security-full`](https://github.com/ArtisanPack-UI/security-full) | Meta-package bundling all of the above |

## Contributing

As an open source project, this package is open to contributions from anyone. Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.
