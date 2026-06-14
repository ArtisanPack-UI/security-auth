<?php

/**
 * Main SecurityAuth class.
 *
 * Resolved from the container as `security-auth` and via the
 * {@see security_auth()} helper. Most public functionality is exposed via the
 * `TwoFactor` facade and the auth-focused services / rules / Livewire
 * components within this package.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth;

class SecurityAuth
{
    public function version(): string
    {
        return '1.0.1';
    }
}
