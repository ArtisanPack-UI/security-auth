<?php

/**
 * SecurityAuth helper functions.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @since      1.0.0
 */

use ArtisanPackUI\SecurityAuth\SecurityAuth;

if ( ! function_exists( 'security_auth' ) ) {
    /**
     * Get the SecurityAuth instance.
     *
     * @since 1.0.0
     *
     * @return SecurityAuth
     */
    function security_auth(): SecurityAuth
    {
        return app( 'security-auth' );
    }
}
