<?php

/**
 * AccountLockoutInterface contract.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Authentication\Contracts;

use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Contracts\Auth\Authenticatable;

interface AccountLockoutInterface
{
    /**
     * Check if a user account is locked.
     */
    public function isUserLocked( Authenticatable $user ): bool;

    /**
     * Check if an IP address is locked.
     */
    public function isIpLocked( string $ipAddress ): bool;

    /**
     * Get the active lockout for a user.
     */
    public function getUserLockout( Authenticatable $user ): ?AccountLockout;

    /**
     * Get the active lockout for an IP.
     */
    public function getIpLockout( string $ipAddress ): ?AccountLockout;

    /**
     * Record a failed authentication attempt.
     *
     * @return array{locked: bool, lockout: ?AccountLockout, attempts_remaining: int}
     */
    public function recordFailedAttempt( string $trigger, ?Authenticatable $user = null, ?string $ipAddress = null ): array;

    /**
     * Clear failed attempts on successful authentication.
     */
    public function clearFailedAttempts( ?Authenticatable $user = null, ?string $ipAddress = null ): void;

    /**
     * Lock a user account.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function lockUser( Authenticatable $user, string $reason, string $lockoutType = 'temporary', ?int $durationMinutes = null, array $metadata = [] ): AccountLockout;

    /**
     * Lock an IP address.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function lockIp( string $ipAddress, int $durationMinutes, string $reason, string $lockoutType = 'temporary', array $metadata = [] ): AccountLockout;

    /**
     * Unlock a user account.
     */
    public function unlockUser( Authenticatable $user, ?Authenticatable $unlockedBy = null, ?string $reason = null ): bool;

    /**
     * Unlock an IP address.
     */
    public function unlockIp( string $ipAddress, ?Authenticatable $unlockedBy = null, ?string $reason = null ): bool;

    /**
     * Get the remaining lockout duration in seconds.
     */
    public function getRemainingLockoutDuration( AccountLockout $lockout ): int;

    /**
     * Calculate progressive lockout duration based on lockout count.
     */
    public function calculateProgressiveDuration( int $lockoutCount ): int;

    /**
     * Check if user should be permanently locked.
     */
    public function shouldPermanentlyLock( Authenticatable $user ): bool;

    /**
     * Get lockout history for a user.
     *
     * @return \Illuminate\Support\Collection<int, AccountLockout>
     */
    public function getLockoutHistory( Authenticatable $user ): \Illuminate\Support\Collection;

    /**
     * Process automatic unlock of expired lockouts.
     */
    public function processExpiredLockouts(): int;

    /**
     * Check if an entity is whitelisted.
     */
    public function isWhitelisted( ?Authenticatable $user = null, ?string $ipAddress = null): bool;
}
