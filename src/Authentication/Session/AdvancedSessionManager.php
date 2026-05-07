<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Authentication\Session;

use ArtisanPackUI\SecurityAuth\Authentication\Contracts\SessionSecurityInterface;
use ArtisanPackUI\SecurityAuth\Models\UserSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdvancedSessionManager implements SessionSecurityInterface
{
    /**
     * Create a new secure session for the user.
     */
    public function createSession( Authenticatable $user, Request $request, string $authMethod, array $metadata = [] ): UserSession
    {
        // Generate session ID
        $sessionId = Str::random( 64 );

        // Get device if available
        $deviceId = $metadata['device_id'] ?? null;

        // Get location
        $location = $this->getLocationFromRequest( $request );

        // Calculate expiration
        $absoluteMinutes = config( 'artisanpack.security-auth.advanced_sessions.timeouts.absolute_minutes', 480 );
        $expiresAt       = now()->addMinutes( $absoluteMinutes );

        return UserSession::create( [
            'id'               => $sessionId,
            'user_id'          => $user->getAuthIdentifier(),
            'device_id'        => $deviceId,
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'location'         => $location,
            'auth_method'      => $authMethod,
            'is_current'       => true,
            'last_activity_at' => now(),
            'expires_at'       => $expiresAt,
            'created_at'       => now(),
        ] );
    }

    /**
     * Validate session bindings.
     */
    public function validateSessionBindings( UserSession $session, Request $request ): array
    {
        $violations = [];
        $config     = config( 'artisanpack.security-auth.advanced_sessions.binding', [] );

        // IP address binding
        if ( $config['ip_address']['enabled'] ?? true ) {
            $strictness = $config['ip_address']['strictness'] ?? 'subnet';
            if ( ! $this->validateIpBinding( $session->ip_address, $request->ip(), $strictness ) ) {
                $violations[] = 'ip_address';
            }
        }

        // User agent binding
        if ( $config['user_agent']['enabled'] ?? true ) {
            $strictness = $config['user_agent']['strictness'] ?? 'exact';
            if ( ! $this->validateUserAgentBinding( $session->user_agent, $request->userAgent(), $strictness ) ) {
                $violations[] = 'user_agent_mismatch';
            }
        }

        return [
            'valid'      => empty( $violations ),
            'violations' => $violations,
        ];
    }

    /**
     * Update session activity timestamp.
     */
    public function touchSession( UserSession $session ): void
    {
        $session->touchActivity();
    }

    /**
     * Rotate the session ID by creating a new session and deleting the old one.
     *
     * This method creates a new session record with a fresh ID while preserving
     * all session metadata, then deletes the old session record. The operation
     * is wrapped in a transaction to ensure atomicity.
     */
    public function rotateSession( UserSession $session ): UserSession
    {
        return DB::transaction( function () use ( $session ) {
            $oldId = $session->id;

            // Create new session with fresh ID, copying all relevant attributes
            $newSession = UserSession::create( [
                'id'               => Str::random( 64 ),
                'user_id'          => $session->user_id,
                'device_id'        => $session->device_id,
                'ip_address'       => $session->ip_address,
                'user_agent'       => $session->user_agent,
                'location'         => $session->location,
                'payload'          => $session->payload,
                'auth_method'      => $session->auth_method,
                'is_current'       => $session->is_current,
                'last_activity_at' => now(),
                'expires_at'       => $session->expires_at,
                'created_at'       => $session->created_at,
            ] );

            // Delete the old session record
            UserSession::where( 'id', $oldId )->delete();

            return $newSession;
        } );
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession( string $sessionId ): bool
    {
        return (bool) UserSession::where( 'id', $sessionId )->delete();
    }

    /**
     * Terminate all sessions except the current one.
     */
    public function terminateOtherSessions( Authenticatable $user, string $currentSessionId ): int
    {
        return UserSession::where( 'user_id', $user->getAuthIdentifier() )
            ->where( 'id', '!=', $currentSessionId )
            ->delete();
    }

    /**
     * Terminate all sessions for a user.
     */
    public function terminateAllSessions( Authenticatable $user ): int
    {
        return UserSession::where( 'user_id', $user->getAuthIdentifier() )->delete();
    }

    /**
     * Get all active sessions for a user.
     */
    public function getUserSessions( Authenticatable $user ): Collection
    {
        return UserSession::where( 'user_id', $user->getAuthIdentifier() )
            ->active()
            ->latestActivity()
            ->get();
    }

    /**
     * Get the current session.
     */
    public function getCurrentSession( Request $request ): ?UserSession
    {
        // Look up by Laravel session ID stored in metadata, or by is_current flag
        $customSessionId = session()->get( 'advanced_session_id' );
        
        if ( ! $customSessionId ) {
            return null;
        }

        return UserSession::where( 'id', $customSessionId )->first();
    }

    /**
     * Check if concurrent session limit is exceeded.
     */
    public function isSessionLimitExceeded( Authenticatable $user ): bool
    {
        $maxSessions  = config( 'artisanpack.security-auth.advanced_sessions.concurrent_sessions.max_sessions', 5 );
        $currentCount = UserSession::where( 'user_id', $user->getAuthIdentifier() )
            ->active()
            ->count();

        return $currentCount >= $maxSessions;
    }

    /**
     * Enforce concurrent session limit.
     */
    public function enforceSessionLimit( Authenticatable $user ): int
    {
        if ( ! config( 'artisanpack.security-auth.advanced_sessions.concurrent_sessions.enabled', true ) ) {
            return 0;
        }

        $maxSessions = config( 'artisanpack.security-auth.advanced_sessions.concurrent_sessions.max_sessions', 5 );
        $strategy    = config( 'artisanpack.security-auth.advanced_sessions.concurrent_sessions.strategy', 'oldest' );

        $activeSessions = UserSession::where( 'user_id', $user->getAuthIdentifier() )
            ->active()
            ->orderBy( 'created_at', 'oldest' === $strategy ? 'asc' : 'desc' )
            ->get();

        $terminatedCount = 0;
        $excessCount     = $activeSessions->count() - $maxSessions + 1; // +1 for the new session

        if ( $excessCount > 0 ) {
            $sessionsToTerminate = $activeSessions->take( $excessCount );

            foreach ( $sessionsToTerminate as $session ) {
                $session->delete();
                $terminatedCount++;
            }
        }

        return $terminatedCount;
    }

    /**
     * Check if session is expired.
     */
    public function isSessionExpired( UserSession $session ): bool
    {
        // Check absolute expiration
        if ( $session->expires_at && $session->expires_at->isPast() ) {
            return true;
        }

        // Check idle timeout only if last_activity_at is set
        if ( $session->last_activity_at ) {
            $idleMinutes = config( 'artisanpack.security-auth.advanced_sessions.timeouts.idle_minutes', 30 );

            return $session->isIdle( $idleMinutes );
        }

        return false;
    }

    /**
     * Check if session is approaching idle timeout.
     */
    public function isApproachingIdleTimeout( UserSession $session ): bool
    {
        $warningMinutes = config( 'artisanpack.security-auth.advanced_sessions.timeouts.idle_warning_minutes', 25 );

        if ( ! $session->last_activity_at ) {
            return false;
        }

        return $session->last_activity_at->addMinutes( $warningMinutes )->isPast();
    }

    /**
     * Prune expired sessions.
     */
    public function pruneExpiredSessions(): int
    {
        return UserSession::expired()->delete();
    }

    /**
     * Check if session should be rotated.
     */
    public function shouldRotateSession( UserSession $session ): bool
    {
        if ( ! config( 'artisanpack.security-auth.advanced_sessions.rotation.enabled', true ) ) {
            return false;
        }

        $intervalMinutes = config( 'artisanpack.security-auth.advanced_sessions.rotation.interval_minutes', 15 );

        if ( ! $session->created_at ) {
            return false;
        }

        return $session->created_at->addMinutes( $intervalMinutes )->isPast();
    }

    /**
     * Get location from request (simplified).
     *
     * @return array<string, mixed>|null
     */
    protected function getLocationFromRequest( Request $request ): ?array
    {
        return [
            'ip'      => $request->ip(),
            'country' => null,
            'region'  => null,
            'city'    => null,
        ];
    }

    /**
     * Validate IP address binding.
     */
    protected function validateIpBinding( ?string $sessionIp, ?string $requestIp, string $strictness ): bool
    {
        if ( ! $sessionIp || ! $requestIp ) {
            return true;
        }

        if ( 'none' === $strictness ) {
            return true;
        }

        if ( 'exact' === $strictness ) {
            return $sessionIp === $requestIp;
        }

        // Subnet matching
        if ( 'subnet' === $strictness ) {
            // For IPv4, compare first 3 octets
            if ( filter_var( $sessionIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )
                && filter_var( $requestIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                $sessionParts = explode( '.', $sessionIp );
                $requestParts = explode( '.', $requestIp );

                return array_slice( $sessionParts, 0, 3 ) === array_slice( $requestParts, 0, 3 );
            }

            // For IPv6, compare first 64 bits
            // Simplified - in production use proper subnet comparison
            return $sessionIp === $requestIp;
        }

        return true;
    }

    /**
     * Validate user agent binding.
     */
    protected function validateUserAgentBinding( ?string $sessionUa, ?string $requestUa, string $strictness ): bool
    {
        if ( ! $sessionUa || ! $requestUa ) {
            return true;
        }

        if ( 'none' === $strictness ) {
            return true;
        }

        if ( 'exact' === $strictness ) {
            return $sessionUa === $requestUa;
        }

        // Browser only - check if browser matches
        if ( 'browser_only' === $strictness ) {
            $sessionBrowser = $this->extractBrowser( $sessionUa );
            $requestBrowser = $this->extractBrowser( $requestUa );

            return $sessionBrowser === $requestBrowser;
        }

        return true;
    }

    /**
     * Extract browser from user agent.
     */
    protected function extractBrowser( string $userAgent ): ?string
    {
        if ( preg_match( '/Chrome\/\d+/i', $userAgent ) ) {
            return 'Chrome';
        }
        if ( preg_match( '/Firefox\/\d+/i', $userAgent)) {
            return 'Firefox';
        }
        if ( preg_match( '/Safari\/\d+/i', $userAgent) && ! str_contains( $userAgent, 'Chrome')) {
            return 'Safari';
        }
        if ( preg_match( '/Edg\/\d+/i', $userAgent)) {
            return 'Edge';
        }

        return null;
    }
}
