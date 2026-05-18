<?php

/**
 * SessionSecurityInterface contract.
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

use ArtisanPackUI\SecurityAuth\Models\UserSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface SessionSecurityInterface
{
    /**
     * Create a new secure session for the user.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function createSession( Authenticatable $user, Request $request, string $authMethod, array $metadata = [] ): UserSession;

    /**
     * Validate session bindings (IP, user agent, device).
     *
     * @return array{valid: bool, violations: array<string>}
     */
    public function validateSessionBindings( UserSession $session, Request $request ): array;

    /**
     * Update session activity timestamp.
     */
    public function touchSession( UserSession $session ): void;

    /**
     * Rotate the session ID for security.
     */
    public function rotateSession( UserSession $session ): UserSession;

    /**
     * Terminate a specific session.
     */
    public function terminateSession( string $sessionId ): bool;

    /**
     * Terminate all sessions for a user except the current one.
     */
    public function terminateOtherSessions( Authenticatable $user, string $currentSessionId ): int;

    /**
     * Terminate all sessions for a user.
     */
    public function terminateAllSessions( Authenticatable $user ): int;

    /**
     * Get all active sessions for a user.
     *
     * @return \Illuminate\Support\Collection<int, UserSession>
     */
    public function getUserSessions( Authenticatable $user ): \Illuminate\Support\Collection;

    /**
     * Get the current session for the request.
     */
    public function getCurrentSession( Request $request ): ?UserSession;

    /**
     * Check if concurrent session limit is exceeded.
     */
    public function isSessionLimitExceeded( Authenticatable $user ): bool;

    /**
     * Enforce concurrent session limit (terminate oldest if needed).
     */
    public function enforceSessionLimit( Authenticatable $user ): int;

    /**
     * Check if session is expired (idle or absolute timeout).
     */
    public function isSessionExpired( UserSession $session ): bool;

    /**
     * Check if session is approaching idle timeout.
     */
    public function isApproachingIdleTimeout( UserSession $session ): bool;

    /**
     * Prune expired sessions from database.
     */
    public function pruneExpiredSessions(): int;
}
