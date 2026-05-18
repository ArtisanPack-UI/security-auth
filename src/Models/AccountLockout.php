<?php

/**
 * AccountLockout Eloquent model.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLockout extends Model
{
    /**
     * Lockout types.
     */
    public const TYPE_TEMPORARY = 'temporary';

    public const TYPE_PERMANENT = 'permanent';

    public const TYPE_SOFT = 'soft';

    /**
     * The table associated with the model.
     */
    protected $table = 'account_lockouts';

    /**
     * The attributes that are mass assignable.
     *
     * Note: is_active is a computed property (see isActive() method), not stored in DB.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'lockout_type',
        'reason',
        'failed_attempts',
        'locked_at',
        'expires_at',
        'unlocked_at',
        'unlocked_by',
        'unlock_reason',
        'metadata',
    ];

    /**
     * Set is_active (ignored - active status is computed).
     */
    public function setIsActiveAttribute( ?bool $value ): void
    {
        // is_active is a computed property, not stored
    }

    /**
     * Get the user associated with this lockout.
     *
     * @return BelongsTo<\Illuminate\Foundation\Auth\User, AccountLockout>
     */
    public function user(): BelongsTo
    {
        $userModel = config( 'auth.providers.users.model', 'App\\Models\\User' );

        return $this->belongsTo( $userModel );
    }

    /**
     * Get the user who unlocked this lockout.
     *
     * @return BelongsTo<\Illuminate\Foundation\Auth\User, AccountLockout>
     */
    public function unlocker(): BelongsTo
    {
        $userModel = config( 'auth.providers.users.model', 'App\\Models\\User' );

        return $this->belongsTo( $userModel, 'unlocked_by' );
    }

    /**
     * Check if the lockout is currently active.
     */
    public function isActive(): bool
    {
        // If already unlocked, not active
        if ( null !== $this->unlocked_at ) {
            return false;
        }

        // Permanent lockouts are always active until unlocked
        if ( self::TYPE_PERMANENT === $this->lockout_type ) {
            return true;
        }

        // Temporary lockouts are active until expired
        if ( null !== $this->expires_at ) {
            return $this->expires_at->isFuture();
        }

        return true;
    }

    /**
     * Get remaining lockout duration in seconds.
     */
    public function getRemainingSeconds(): int
    {
        if ( ! $this->isActive() || null === $this->expires_at ) {
            return 0;
        }

        return max( 0, now()->diffInSeconds( $this->expires_at, false ) );
    }

    /**
     * Unlock this lockout.
     */
    public function unlock( ?int $unlockedBy = null, ?string $reason = null ): void
    {
        $this->unlocked_at   = now();
        $this->unlocked_by   = $unlockedBy;
        $this->unlock_reason = $reason;
        $this->save();
    }

    /**
     * Check if this is a temporary lockout.
     */
    public function isTemporary(): bool
    {
        return self::TYPE_TEMPORARY === $this->lockout_type;
    }

    /**
     * Check if this is a permanent lockout.
     */
    public function isPermanent(): bool
    {
        return self::TYPE_PERMANENT === $this->lockout_type;
    }

    /**
     * Check if this is a soft lockout (CAPTCHA required).
     */
    public function isSoft(): bool
    {
        return self::TYPE_SOFT === $this->lockout_type;
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata( string $key ): mixed
    {
        return data_get( $this->metadata, $key );
    }

    /**
     * Set a metadata value.
     */
    public function setMetadata( string $key, mixed $value ): void
    {
        $metadata = $this->metadata ?? [];
        data_set( $metadata, $key, $value );
        $this->metadata = $metadata;
    }

    /**
     * Scope a query to only include active lockouts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AccountLockout>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<AccountLockout>
     */
    public function scopeActive( $query )
    {
        // Mirror isActive(): a lockout is active when it hasn't been manually
        // unlocked AND (it's permanent OR has no expiry OR expires in the future).
        return $query->whereNull( 'unlocked_at' )
            ->where( function ( $q ): void {
                $q->where( 'lockout_type', self::TYPE_PERMANENT )
                    ->orWhereNull( 'expires_at' )
                    ->orWhere( 'expires_at', '>', now() );
            } );
    }

    /**
     * Scope a query to only include lockouts for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AccountLockout>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<AccountLockout>
     */
    public function scopeForUser( $query, int $userId )
    {
        return $query->where( 'user_id', $userId );
    }

    /**
     * Scope a query to only include lockouts for a specific IP.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AccountLockout>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<AccountLockout>
     */
    public function scopeForIp( $query, string $ipAddress )
    {
        return $query->where( 'ip_address', $ipAddress );
    }

    /**
     * Scope a query to only include expired lockouts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AccountLockout>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<AccountLockout>
     */
    public function scopeExpired( $query )
    {
        return $query->whereNull( 'unlocked_at' )
            ->where( 'lockout_type', '!=', self::TYPE_PERMANENT )
            ->where( 'expires_at', '<=', now() );
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating( function ( AccountLockout $lockout ): void {
            if ( null === $lockout->locked_at ) {
                $lockout->locked_at = now();
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
            'failed_attempts' => 'integer',
            'locked_at'       => 'datetime',
            'expires_at'      => 'datetime',
            'unlocked_at'     => 'datetime',
            'metadata'        => 'array',
        ];
    }
}
