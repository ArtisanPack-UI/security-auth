<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Contracts;

/**
 * Minimal contract for logging authentication-related security events.
 *
 * Implementations live in downstream packages (e.g. `security-analytics`).
 * security-auth treats this as an optional dependency — when an
 * implementation is bound in the container, the package's middleware and
 * services log through it; otherwise events are silently dropped.
 *
 * @since 1.0.0
 */
interface AuthEventLoggerInterface
{
    /**
     * Log an authentication-related event with arbitrary contextual data.
     *
     * @param  string                $event    Short event name, e.g. `password_validation_failed`.
     * @param  array<string, mixed>  $context  Event metadata.
     */
    public function authentication( string $event, array $context = [] ): void;
}
