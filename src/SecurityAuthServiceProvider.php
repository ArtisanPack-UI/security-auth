<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth;

use ArtisanPackUI\SecurityAuth\Authentication\Contracts\AccountLockoutInterface;
use ArtisanPackUI\SecurityAuth\Authentication\Contracts\SessionSecurityInterface;
use ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager;
use ArtisanPackUI\SecurityAuth\Authentication\Session\AdvancedSessionManager;
use ArtisanPackUI\SecurityAuth\Console\Commands\ManageAccountLockout;
use ArtisanPackUI\SecurityAuth\Contracts\PasswordSecurityServiceInterface;
use ArtisanPackUI\SecurityAuth\Http\Middleware\CheckAccountLockout;
use ArtisanPackUI\SecurityAuth\Http\Middleware\EnforcePasswordPolicy;
use ArtisanPackUI\SecurityAuth\Http\Middleware\StepUpAuthentication;
use ArtisanPackUI\SecurityAuth\Http\Middleware\TwoFactorMiddleware;
use ArtisanPackUI\SecurityAuth\Services\HaveIBeenPwnedService;
use ArtisanPackUI\SecurityAuth\Services\PasswordSecurityService;
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorManager;
use Illuminate\Support\ServiceProvider;

/**
 * Security Auth service provider.
 *
 * Registers two-factor authentication, password security, account lockout,
 * and advanced session management services for Laravel applications.
 *
 * @since 1.0.0
 */
class SecurityAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/artisanpack/security-auth.php',
            'artisanpack.security-auth',
        );

        $this->app->singleton( 'security-auth', function ( $app ) {
            return new SecurityAuth();
        } );

        $this->registerTwoFactor();
        $this->registerPasswordSecurity();
        $this->registerAccountLockout();
        $this->registerAdvancedSessions();
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/artisanpack/security-auth.php' => config_path( 'artisanpack/security-auth.php' ),
            ],
            'security-auth-config',
        );

        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );
        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations/password' );
        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations/authentication' );

        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerLivewireComponents();
    }

    protected function registerTwoFactor(): void
    {
        $this->app->singleton( TwoFactorManager::class, function () {
            return new TwoFactorManager();
        } );

        $this->app->alias( TwoFactorManager::class, 'security-auth.two-factor' );
    }

    protected function registerPasswordSecurity(): void
    {
        $this->app->singleton( HaveIBeenPwnedService::class, function () {
            return new HaveIBeenPwnedService();
        } );

        $this->app->singleton( PasswordSecurityServiceInterface::class, function ( $app ) {
            return new PasswordSecurityService(
                $app->make( HaveIBeenPwnedService::class ),
            );
        } );
    }

    protected function registerAccountLockout(): void
    {
        $this->app->singleton( AccountLockoutManager::class, function () {
            return new AccountLockoutManager();
        } );

        $this->app->bind( AccountLockoutInterface::class, AccountLockoutManager::class );
    }

    protected function registerAdvancedSessions(): void
    {
        $this->app->singleton( AdvancedSessionManager::class, function () {
            return new AdvancedSessionManager();
        } );

        $this->app->bind( SessionSecurityInterface::class, AdvancedSessionManager::class );
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware( 'two-factor', TwoFactorMiddleware::class );
        $router->aliasMiddleware( 'password.policy', EnforcePasswordPolicy::class );
        $router->aliasMiddleware( 'check.lockout', CheckAccountLockout::class );
        $router->aliasMiddleware( 'step-up', StepUpAuthentication::class );
    }

    protected function registerCommands(): void
    {
        if ( ! $this->app->runningInConsole() ) {
            return;
        }

        $this->commands(
            [
                ManageAccountLockout::class,
            ],
        );
    }

    protected function registerLivewireComponents(): void
    {
        if ( ! class_exists( \Livewire\Livewire::class ) || ! $this->app->bound( 'livewire' ) ) {
            return;
        }

        \Livewire\Livewire::component( 'password-strength-meter', Livewire\PasswordStrengthMeter::class );
        \Livewire\Livewire::component( 'account-lockout-status', Livewire\AccountLockoutStatus::class );
        \Livewire\Livewire::component( 'session-manager', Livewire\SessionManager::class );
        \Livewire\Livewire::component( 'step-up-authentication-modal', Livewire\StepUpAuthenticationModal::class );
    }
}
