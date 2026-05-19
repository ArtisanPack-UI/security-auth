<?php

/**
 * BreachCheckerInterface contract.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Contracts;

interface BreachCheckerInterface
{
    /**
     * Check if a password has been exposed in known data breaches.
     *
     * @param  string  $password  The plain-text password to check
     *
     * @return int Number of times password has been seen in breaches (0 if not found)
     */
    public function check( string $password ): int;

    /**
     * Check if password is compromised (boolean convenience method).
     *
     * @param  string  $password  The plain-text password to check
     *
     * @return bool True if password has been compromised
     */
    public function isCompromised( string $password ): bool;
}
