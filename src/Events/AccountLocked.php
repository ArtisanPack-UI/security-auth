<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Events;

use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountLocked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AccountLockout $lockout,
        public mixed $user = null,
        public ?string $ipAddress = null,
    ) {
    }

    /**
     * Check if this is a permanent lockout.
     */
    public function isPermanent(): bool
    {
        return $this->lockout->isPermanent();
    }

    /**
     * Get the lockout type.
     */
    public function getLockoutType(): string
    {
        return $this->lockout->lockout_type;
    }

    /**
     * Get the reason for lockout.
     */
    public function getReason(): ?string
    {
        return $this->lockout->reason;
    }
}
