<?php

declare( strict_types=1 );

use ArtisanPackUI\SecurityAuth\TwoFactor\Providers\EmailProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Two-factor authentication
    |--------------------------------------------------------------------------
    */

    'two_factor' => [
        'default'   => env( 'TWO_FACTOR_PROVIDER', 'email' ),
        'providers' => [
            'email' => [
                'driver' => EmailProvider::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Named routes that the package needs to redirect to. Override these to
    | point at your application's own routes when you've published custom
    | challenge / verification screens.
    |
    */

    'routes' => [
        'verify' => 'two-factor.challenge',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password security
    |--------------------------------------------------------------------------
    */

    'passwordSecurity' => [
        'enabled' => env( 'SECURITY_AUTH_PASSWORD_ENABLED', true ),

        'complexity' => [
            'minLength'                   => 8,
            'maxLength'                   => 128,
            'requireUppercase'            => true,
            'requireLowercase'            => true,
            'requireNumbers'              => true,
            'requireSymbols'              => true,
            'minUniqueCharacters'         => 4,
            'disallowRepeatingCharacters' => 3,
            'disallowSequentialCharacters' => 3,
            'disallowCommonPasswords'     => true,
            'disallowUserAttributes'      => true,
        ],

        'history' => [
            'enabled'               => true,
            'count'                 => 5,
            'minDaysBetweenChanges' => 1,
        ],

        'expiration' => [
            'enabled'      => false,
            'days'         => 90,
            'warningDays'  => 14,
            'graceLogins'  => 3,
            'exemptRoles'  => [],
        ],

        'breachChecking' => [
            'enabled'          => env( 'SECURITY_AUTH_BREACH_CHECK_ENABLED', true ),
            'onRegistration'   => true,
            'onPasswordChange' => true,
            'onLogin'          => false,
            'blockCompromised' => true,
            'apiTimeout'       => 5,
            'cacheResults'     => true,
            'cacheTtl'         => 86400,
        ],

        'strengthMeter' => [
            'enabled'       => true,
            'showFeedback'  => true,
            'minScore'      => 3,
            'showCrackTime' => true,
        ],

        'logging' => [
            'passwordChanges'      => true,
            'failedValidations'    => true,
            'breachDetections'     => true,
            'expirationWarnings'   => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account lockout
    |--------------------------------------------------------------------------
    */

    'account_lockout' => [
        'enabled'           => env( 'SECURITY_AUTH_LOCKOUT_ENABLED', true ),
        'soft_lockout'      => true,
        'permanent_lockout' => false,
        'whitelist'         => [],

        'triggers' => [
            'failed_login'       => true,
            'suspicious_activity' => true,
            'manual'              => true,
        ],

        'lockout_duration' => [
            'initial_minutes' => 5,
            'progressive'     => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced session management
    |--------------------------------------------------------------------------
    */

    'advanced_sessions' => [
        'binding' => [
            'ip_address' => true,
            'user_agent' => true,
        ],

        'rotation' => [
            'enabled'          => true,
            'interval_minutes' => 30,
        ],

        'timeouts' => [
            'idle_minutes'         => 30,
            'absolute_minutes'     => 480,
            'idle_warning_minutes' => 25,
        ],

        'concurrent_sessions' => [
            'enabled'      => true,
            'max_sessions' => 5,
            'strategy'     => 'oldest',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Step-up authentication
    |--------------------------------------------------------------------------
    */

    'step_up_authentication' => [
        'timeout_minutes' => 15,
        'routes'          => [],
    ],

];
