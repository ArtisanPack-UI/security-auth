<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Authentication\Lockout;

use ArtisanPackUI\SecurityAuth\Authentication\Contracts\AccountLockoutInterface;
use ArtisanPackUI\SecurityAuth\Events\AccountLocked;
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AccountLockoutManager implements AccountLockoutInterface
{
    /**
     * Check if a user account is locked.
     */
    public function isUserLocked( Authenticatable $user ): bool
    {
        return AccountLockout::forUser( $user->getAuthIdentifier() )
            ->active()
            ->exists();
    }

    /**
     * Check if an IP address is locked.
     */
    public function isIpLocked( string $ipAddress ): bool
    {
        return AccountLockout::forIp( $ipAddress )
            ->active()
            ->exists();
    }

    /**
     * Get the active lockout for a user.
     */
    public function getUserLockout( Authenticatable $user ): ?AccountLockout
    {
        return AccountLockout::forUser( $user->getAuthIdentifier() )
            ->active()
            ->first();
    }

    /**
     * Get the active lockout for an IP.
     */
    public function getIpLockout( string $ipAddress ): ?AccountLockout
    {
        return AccountLockout::forIp( $ipAddress )
            ->active()
            ->first();
    }

    /**
     * Record a failed authentication attempt.
     */
    public function recordFailedAttempt( string $trigger, ?Authenticatable $user = null, ?string $ipAddress = null ): array
    {
        $config = config( "artisanpack.security-auth.account_lockout.triggers.{$trigger}", [] );

        if ( ! ( $config['enabled'] ?? true ) ) {
            return ['locked' => false, 'lockout' => null, 'attempts_remaining' => PHP_INT_MAX];
        }

        // Skip recording attempts for whitelisted users or IPs
        if ( $this->isWhitelisted( $user, $ipAddress ) ) {
            return ['locked' => false, 'lockout' => null, 'attempts_remaining' => PHP_INT_MAX];
        }

        $threshold     = $config['threshold'] ?? 5;
        $windowMinutes = $config['window_minutes'] ?? 15;

        // Build cache key
        $keyParts = [$trigger];
        if ( $user ) {
            $keyParts[] = 'user_' . $user->getAuthIdentifier();
        }
        if ( $ipAddress ) {
            $keyParts[] = 'ip_' . $ipAddress;
        }
        $cacheKey   = 'lockout_attempts_' . implode( '_', $keyParts );
        $ttlSeconds = $windowMinutes * 60;

        // Use add-if-not-exists to establish the key with TTL for new entries.
        // This ensures the TTL is set atomically with the initial value,
        // preventing race conditions where increment happens before TTL is set.
        Cache::add( $cacheKey, 0, now()->addSeconds( $ttlSeconds ) );

        // Now atomically increment
        $attempts = Cache::increment( $cacheKey );

        // Handle drivers that don't support increment — read, +1, write.
        if ( false === $attempts ) {
            $attempts = (int) Cache::get( $cacheKey, 0 ) + 1;
            Cache::put( $cacheKey, $attempts, now()->addSeconds( $ttlSeconds ) );
        }

        $attemptsRemaining = max( 0, $threshold - $attempts );

        // Check if we should lock
        if ( $attempts >= $threshold ) {
            // Reuse an existing active lockout if one is already in flight so
            // concurrent requests don't insert duplicates and re-fire AccountLocked.
            $existing = $this->getActiveLockoutForSubject( $user, $ipAddress );

            if ( null !== $existing ) {
                return [
                    'locked'             => true,
                    'lockout'            => $existing,
                    'attempts_remaining' => 0,
                ];
            }

            $lockout = $this->createLockout( $trigger, $user, $ipAddress, $attempts );

            return [
                'locked'             => true,
                'lockout'            => $lockout,
                'attempts_remaining' => 0,
            ];
        }

        return [
            'locked'             => false,
            'lockout'            => null,
            'attempts_remaining' => $attemptsRemaining,
        ];
    }

    /**
     * Clear failed attempts on successful authentication.
     */
    public function clearFailedAttempts( ?Authenticatable $user = null, ?string $ipAddress = null ): void
    {
        $triggers = array_keys( config( 'artisanpack.security-auth.account_lockout.triggers', [] ) );

        foreach ( $triggers as $trigger ) {
            $keyParts = [$trigger];
            if ( $user ) {
                $keyParts[] = 'user_' . $user->getAuthIdentifier();
            }
            if ( $ipAddress ) {
                $keyParts[] = 'ip_' . $ipAddress;
            }
            Cache::forget( 'lockout_attempts_' . implode( '_', $keyParts ) );
        }
    }

    /**
     * Lock a user account.
     */
    public function lockUser( Authenticatable $user, string $reason, string $lockoutType = 'temporary', ?int $durationMinutes = null, array $metadata = [] ): AccountLockout
    {
        $durationMinutes = $durationMinutes ?? config( 'artisanpack.security-auth.account_lockout.lockout_duration.initial_minutes', 15 );

        return AccountLockout::create( [
            'user_id'      => $user->getAuthIdentifier(),
            'lockout_type' => $lockoutType,
            'reason'       => $reason,
            'locked_at'    => now(),
            'expires_at'   => AccountLockout::TYPE_PERMANENT === $lockoutType ? null : now()->addMinutes( $durationMinutes ),
            'metadata'     => $metadata,
        ] );
    }

    /**
     * Lock an IP address.
     */
    public function lockIp( string $ipAddress, int $durationMinutes, string $reason, string $lockoutType = 'temporary', array $metadata = [] ): AccountLockout
    {
        return AccountLockout::create( [
            'ip_address'   => $ipAddress,
            'lockout_type' => $lockoutType,
            'reason'       => $reason,
            'locked_at'    => now(),
            'expires_at'   => AccountLockout::TYPE_PERMANENT === $lockoutType ? null : now()->addMinutes( $durationMinutes ),
            'metadata'     => $metadata,
        ] );
    }

    /**
     * Unlock a user account.
     */
    public function unlockUser( Authenticatable $user, ?Authenticatable $unlockedBy = null, ?string $reason = null ): bool
    {
        $lockout = $this->getUserLockout( $user );

        if ( ! $lockout ) {
            return false;
        }

        $lockout->unlock( $unlockedBy?->getAuthIdentifier(), $reason );

        return true;
    }

    /**
     * Unlock an IP address.
     */
    public function unlockIp( string $ipAddress, ?Authenticatable $unlockedBy = null, ?string $reason = null ): bool
    {
        $lockout = $this->getIpLockout( $ipAddress );

        if ( ! $lockout ) {
            return false;
        }

        $lockout->unlock( $unlockedBy?->getAuthIdentifier(), $reason );

        return true;
    }

    /**
     * Get remaining lockout duration in seconds.
     */
    public function getRemainingLockoutDuration( AccountLockout $lockout ): int
    {
        return $lockout->getRemainingSeconds();
    }

    /**
     * Calculate progressive lockout duration based on lockout count.
     */
    public function calculateProgressiveDuration( int $lockoutCount ): int
    {
        $config         = config( 'artisanpack.security-auth.account_lockout', [] );
        $initialMinutes = $config['lockout_duration']['initial_minutes'] ?? 15;
        $maxMinutes     = $config['progressive']['max_duration'] ?? 1440;
        $multiplier     = $config['progressive']['multiplier'] ?? 2.0;
        $progressive    = $config['progressive']['enabled'] ?? true;

        if ( ! $progressive ) {
            return $initialMinutes;
        }

        // Ensure lockoutCount is at least 1 to avoid negative exponents
        // which would result in duration smaller than initial_minutes
        if ( $lockoutCount < 1 ) {
            $lockoutCount = 1;
        }

        // Calculate progressive duration based on count
        // First lockout (count=1) = initial
        // Second lockout (count=2) = initial * multiplier
        // Third lockout (count=3) = initial * multiplier^2
        $duration = (int) ( $initialMinutes * pow( $multiplier, $lockoutCount - 1 ) );

        return min( $duration, $maxMinutes );
    }

    /**
     * Calculate progressive lockout duration for a user.
     */
    public function calculateProgressiveDurationForUser( Authenticatable $user ): int
    {
        // Count recent lockouts
        $recentLockouts = $this->getRecentLockoutCount( $user );

        return $this->calculateProgressiveDuration( $recentLockouts + 1 );
    }

    /**
     * Check if user should be permanently locked.
     */
    public function shouldPermanentlyLock( Authenticatable $user ): bool
    {
        $config = config( 'artisanpack.security-auth.account_lockout.permanent_lockout', [] );

        if ( ! ( $config['enabled'] ?? true ) ) {
            return false;
        }

        $threshold = $config['after_temporary_count'] ?? 5;

        // Count temporary lockouts in last 24 hours
        $temporaryCount = AccountLockout::where( 'user_id', $user->getAuthIdentifier() )
            ->where( 'lockout_type', AccountLockout::TYPE_TEMPORARY )
            ->where( 'created_at', '>=', now()->subHours( 24 ) )
            ->count();

        return $temporaryCount >= $threshold;
    }

    /**
     * Get lockout history for a user.
     */
    public function getLockoutHistory( Authenticatable $user ): Collection
    {
        return AccountLockout::where( 'user_id', $user->getAuthIdentifier() )
            ->orderByDesc( 'created_at' )
            ->get();
    }

    /**
     * Process automatic unlock of expired lockouts.
     */
    public function processExpiredLockouts(): int
    {
        $expired = AccountLockout::expired()->get();
        $count   = 0;

        foreach ( $expired as $lockout ) {
            $lockout->unlock( null, 'Automatic unlock - lockout expired' );
            $count++;
        }

        return $count;
    }

    /**
     * Check if an entity is whitelisted.
     */
    public function isWhitelisted( ?Authenticatable $user = null, ?string $ipAddress = null ): bool
    {
        $config = config( 'artisanpack.security-auth.account_lockout.whitelist', [] );

        $ips   = array_map( 'strval', (array) ( $config['ips'] ?? [] ) );
        $users = array_map( 'strval', (array) ( $config['users'] ?? [] ) );

        if ( $ipAddress && in_array( (string) $ipAddress, $ips, true ) ) {
            return true;
        }

        if ( $user && in_array( (string) $user->getAuthIdentifier(), $users, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Find a still-active lockout for the given user or IP, if one exists.
     */
    protected function getActiveLockoutForSubject( ?Authenticatable $user, ?string $ipAddress ): ?AccountLockout
    {
        $query = AccountLockout::active();

        if ( $user ) {
            return $query->where( 'user_id', $user->getAuthIdentifier() )->first();
        }

        if ( $ipAddress ) {
            return $query->where( 'ip_address', $ipAddress )->first();
        }

        return null;
    }

    /**
     * Create a lockout.
     */
    protected function createLockout( string $trigger, ?Authenticatable $user, ?string $ipAddress, int $failedAttempts ): AccountLockout
    {
        $durationMinutes = $this->calculateDuration( $user );
        $lockoutType     = $this->determineLockoutType( $user );

        $lockout = AccountLockout::create( [
            'user_id'         => $user?->getAuthIdentifier(),
            'ip_address'      => $ipAddress,
            'lockout_type'    => $lockoutType,
            'reason'          => "Too many failed {$trigger} attempts",
            'failed_attempts' => $failedAttempts,
            'locked_at'       => now(),
            'expires_at'      => AccountLockout::TYPE_PERMANENT === $lockoutType ? null : now()->addMinutes( $durationMinutes ),
            'metadata'        => [
                'trigger' => $trigger,
            ],
        ] );

        // Clear the attempts counter
        $this->clearFailedAttempts( $user, $ipAddress );

        // Dispatch the AccountLocked event so listeners can respond
        event( new AccountLocked( $lockout, $user, $ipAddress ) );

        return $lockout;
    }

    /**
     * Determine the lockout type.
     */
    protected function determineLockoutType( ?Authenticatable $user ): string
    {
        if ( ! $user ) {
            return AccountLockout::TYPE_TEMPORARY;
        }

        // Check if should be permanent
        if ( $this->shouldPermanentlyLock( $user ) ) {
            return AccountLockout::TYPE_PERMANENT;
        }

        // Check soft lockout threshold
        $softConfig = config( 'artisanpack.security-auth.account_lockout.soft_lockout', [] );
        if ( $softConfig['enabled'] ?? true ) {
            $recentLockouts = $this->getRecentLockoutCount( $user );
            if ( 0 === $recentLockouts ) {
                return AccountLockout::TYPE_SOFT;
            }
        }

        return AccountLockout::TYPE_TEMPORARY;
    }

    /**
     * Get recent lockout count for progressive lockout.
     */
    protected function getRecentLockoutCount( ?Authenticatable $user ): int
    {
        if ( ! $user ) {
            return 0;
        }

        return AccountLockout::where( 'user_id', $user->getAuthIdentifier() )
            ->where( 'created_at', '>=', now()->subHours( 24 ) )
            ->count();
    }

    /**
     * Calculate duration for a lockout.
     */
    protected function calculateDuration( ?Authenticatable $user ): int
    {
        if ( ! $user ) {
            return config( 'artisanpack.security-auth.account_lockout.lockout_duration.initial_minutes', 15);
        }

        return $this->calculateProgressiveDurationForUser( $user);
    }
}
