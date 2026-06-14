# ArtisanPack UI — Security Auth Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Widened `illuminate/support` constraint to accept Laravel 13 (`^10.0|^11.0|^12.0|^13.0`).
- Widened `pragmarx/google2fa-laravel` constraint to accept v3 (`^2.3|^3.0`), which is the Laravel-13-compatible line. The package's only consumer (`TwoFactorAuthenticatable::generateTwoFactorSecret`) calls `PragmaRX\Google2FA\Google2FA::generateSecretKey()` on the underlying `pragmarx/google2fa` core library, whose API is unchanged across the v2→v3 wrapper bump.

## [1.0.0] - 2026-05-18

### Added

- Initial release of the standalone Security Auth package, extracted from `artisanpack-ui/security` 1.x as part of the Security 2.0 package split.
- **Two-factor authentication**: `TwoFactor` Facade, `TwoFactorManager`, `EmailProvider` (default), `TwoFactorAuthenticatable` trait for User models, `TwoFactorCodeMailable` for email delivery.
- **Password security**: `PasswordSecurityService` (308 lines) for complexity validation, history enforcement, HaveIBeenPwned breach checks, and expiration tracking. Backed by `HaveIBeenPwnedService` (136 lines) for the breach lookups.
- **Validation rules**: `PasswordComplexity`, `NotCompromised`, `PasswordHistoryRule`, `PasswordPolicy` (composite).
- **Account lockout**: `AccountLockoutManager` (432 lines) supporting user-level and IP-level lockouts with configurable durations, failed-attempt tracking, and historical lockout audit.
- **Advanced session management**: `AdvancedSessionManager` (415 lines) for session bindings (IP + UA), session rotation, concurrent session limits, and programmatic termination.
- **Middleware aliases**: `two-factor`, `password.policy`, `check.lockout`, `step-up`.
- **Livewire components** (4): `PasswordStrengthMeter`, `AccountLockoutStatus`, `SessionManager`, `StepUpAuthenticationModal` — all with shipped Blade views in plain HTML + Tailwind.
- **Eloquent models** (3): `AccountLockout`, `PasswordHistory`, `UserSession`.
- **Migrations** (3 groups): adds `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_enabled_at` columns to `users`; password history table + extra password security columns on `users`; user sessions + account lockouts tables.
- **Artisan command**: `security:lockout` for managing lockouts (list / lock / unlock / clear).
- **Event**: `AccountLocked`.
- **Service contracts**: `AccountLockoutInterface`, `SessionSecurityInterface`, `PasswordSecurityServiceInterface`, `BreachCheckerInterface`, `AuthEventLoggerInterface` for swapping implementations.

### Fixed

- Wrote the 4 missing Livewire Blade views (`password-strength-meter`, `account-lockout-status`, `session-manager`, `step-up-authentication-modal`) — without them every Livewire render threw `View not found` in production.
- Added view-render smoke tests for each Livewire component to prevent regression.
- Author email normalized to `support@artisanpackui.dev`.
- License switched from GPL-3.0-or-later to MIT to match the rest of the ecosystem.

### Removed

- This package contains the auth security content previously bundled in `artisanpack-ui/security` 1.x. See the [`artisanpack-ui/security` UPGRADE guide](https://github.com/ArtisanPack-UI/security/blob/main/UPGRADE.md) for migration instructions from 1.x.
