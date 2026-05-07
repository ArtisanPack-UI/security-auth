<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    /**
     * Authentication methods.
     */
    public const AUTH_PASSWORD = 'password';

    public const AUTH_SOCIAL = 'social';

    public const AUTH_SSO = 'sso';

    public const AUTH_WEBAUTHN = 'webauthn';

    public const AUTH_BIOMETRIC = 'biometric';

    public const AUTH_2FA = '2fa';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The table associated with the model.
     */
    protected $table = 'user_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'session_token',
        'user_id',
        'device_id',
        'ip_address',
        'user_agent',
        'location',
        'payload',
        'auth_method',
        'is_current',
        'last_activity_at',
        'expires_at',
        'terminated_at',
        'created_at',
    ];

    /**
     * Set session_token (alias for id).
     */
    public function setSessionTokenAttribute( ?string $value ): void
    {
        $this->attributes['id'] = $value;
    }

    /**
     * Get session_token (alias for id).
     */
    public function getSessionTokenAttribute(): ?string
    {
        return $this->id;
    }

    /**
     * Get the user that owns this session.
     *
     * @return BelongsTo<\Illuminate\Foundation\Auth\User, UserSession>
     */
    public function user(): BelongsTo
    {
        $userModel = config( 'auth.providers.users.model', 'App\\Models\\User' );

        return $this->belongsTo( $userModel );
    }

    /**
     * Get the device associated with this session.
     *
     * UserDevice lives in artisanpack-ui/security-advanced-auth, which is
     * an optional sibling package. Resolve the class lazily via FQCN string
     * so the relation is a no-op (returns nothing) when that package isn't
     * installed instead of throwing a ClassNotFoundException at boot.
     *
     * @return BelongsTo<Model, UserSession>
     */
    public function device(): BelongsTo
    {
        $deviceModel = '\\ArtisanPackUI\\SecurityAdvancedAuth\\Models\\UserDevice';

        if ( ! class_exists( $deviceModel ) ) {
            // Fall back to a self-referential nullable relation: device_id
            // is nullable, so the query returns no rows in this configuration.
            return $this->belongsTo( static::class, 'device_id' );
        }

        return $this->belongsTo( $deviceModel, 'device_id' );
    }

    /**
     * Check if the session is expired.
     */
    public function isExpired(): bool
    {
        return null !== $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the session is active (not expired and not terminated).
     */
    public function isActive(): bool
    {
        // A session is active if it's not expired and not terminated
        if ( null !== $this->terminated_at ) {
            return false;
        }

        return ! $this->isExpired();
    }

    /**
     * Check if the session is idle (no activity within threshold).
     */
    public function isIdle( int $idleMinutes ): bool
    {
        if ( null === $this->last_activity_at ) {
            return true;
        }

        return $this->last_activity_at->copy()->addMinutes( $idleMinutes )->isPast();
    }

    /**
     * Touch the last activity timestamp.
     */
    public function touchActivity(): bool
    {
        $this->last_activity_at = now();

        return $this->save();
    }

    /**
     * Get a display name for the session location.
     */
    public function getLocationDisplay(): string
    {
        if ( empty( $this->location ) ) {
            return 'Unknown location';
        }

        $parts = [];

        if ( ! empty( $this->location['city'] ) ) {
            $parts[] = $this->location['city'];
        }

        if ( ! empty( $this->location['region'] ) ) {
            $parts[] = $this->location['region'];
        }

        if ( ! empty( $this->location['country'] ) ) {
            $parts[] = $this->location['country'];
        }

        return implode( ', ', $parts ) ?: 'Unknown location';
    }

    /**
     * Get a parsed user agent.
     *
     * @return array{browser: ?string, os: ?string}
     */
    public function getParsedUserAgent(): array
    {
        // Simple parsing - can be enhanced with a proper user agent parser
        $ua      = $this->user_agent ?? '';
        $browser = null;
        $os      = null;

        // Detect browser
        if ( str_contains( $ua, 'Chrome' ) ) {
            $browser = 'Chrome';
        } elseif ( str_contains( $ua, 'Firefox' ) ) {
            $browser = 'Firefox';
        } elseif ( str_contains( $ua, 'Safari' ) ) {
            $browser = 'Safari';
        } elseif ( str_contains( $ua, 'Edge' ) ) {
            $browser = 'Edge';
        }

        // Detect OS
        if ( str_contains( $ua, 'Windows' ) ) {
            $os = 'Windows';
        } elseif ( str_contains( $ua, 'Mac OS' ) ) {
            $os = 'macOS';
        } elseif ( str_contains( $ua, 'Linux' ) ) {
            $os = 'Linux';
        } elseif ( str_contains( $ua, 'Android' ) ) {
            $os = 'Android';
        } elseif ( str_contains( $ua, 'iOS' ) || str_contains( $ua, 'iPhone' ) || str_contains( $ua, 'iPad' ) ) {
            $os = 'iOS';
        }

        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * Scope a query to only include active (non-expired) sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<UserSession>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<UserSession>
     */
    public function scopeActive( $query )
    {
        return $query->where( function ( $q ): void {
            $q->whereNull( 'expires_at' )
                ->orWhere( 'expires_at', '>', now() );
        } )->whereNull( 'terminated_at' );
    }

    /**
     * Scope a query to only include expired sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<UserSession>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<UserSession>
     */
    public function scopeExpired( $query )
    {
        return $query->where( 'expires_at', '<=', now() );
    }

    /**
     * Scope a query to order by most recent activity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<UserSession>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<UserSession>
     */
    public function scopeLatestActivity( $query )
    {
        return $query->orderByDesc( 'last_activity_at' );
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating( function ( UserSession $session ): void {
            if ( empty( $session->id ) ) {
                $session->id = \Illuminate\Support\Str::random( 64 );
            }
            if ( null === $session->created_at ) {
                $session->created_at = now();
            }
        } );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location'         => 'array',
            'is_current'       => 'boolean',
            'last_activity_at' => 'datetime',
            'expires_at'       => 'datetime',
            'terminated_at'    => 'datetime',
            'created_at'       => 'datetime',
        ];
    }
}
