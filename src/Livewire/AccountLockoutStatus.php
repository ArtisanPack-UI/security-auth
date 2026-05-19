<?php

/**
 * AccountLockoutStatus Livewire component.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Livewire;

use ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager;
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AccountLockoutStatus extends Component
{
    use WithPagination;

    public ?array $currentLockout = null;

    public int $perPage = 10;

    protected AccountLockoutManager $lockoutManager;

    public function boot( AccountLockoutManager $lockoutManager ): void
    {
        $this->lockoutManager = $lockoutManager;
    }

    public function mount(): void
    {
        $this->loadCurrentLockout();
    }

    public function loadCurrentLockout(): void
    {
        $user = Auth::user();

        if ( ! $user ) {
            return;
        }

        $lockout = $this->lockoutManager->getUserLockout( $user );

        if ( $lockout && $lockout->isActive() ) {
            $this->currentLockout = [
                'id'                => $lockout->id,
                'type'              => $lockout->lockout_type,
                'type_label'        => $this->getLockoutTypeLabel( $lockout->lockout_type ),
                'reason'            => $lockout->reason,
                'expires_at'        => $lockout->expires_at?->format( 'M j, Y g:i A' ),
                'remaining_minutes' => $lockout->expires_at ? ceil( now()->diffInMinutes( $lockout->expires_at, false ) ) : null,
                'is_permanent'      => $lockout->isPermanent(),
            ];
        } else {
            $this->currentLockout = null;
        }
    }

    public function getLockoutHistoryProperty()
    {
        $user = Auth::user();

        if ( ! $user ) {
            return collect();
        }

        return AccountLockout::where( 'user_id', $user->id )
            ->orderBy( 'created_at', 'desc' )
            ->paginate( $this->perPage );
    }

    public function render()
    {
        return view( 'security-auth::livewire.account-lockout-status', [
            'lockoutHistory' => $this->lockoutHistory,
        ] );
    }

    protected function getLockoutTypeLabel( string $type ): string
    {
        return match ( $type ) {
            AccountLockout::TYPE_TEMPORARY   => 'Temporary Lockout',
            AccountLockout::TYPE_PROGRESSIVE => 'Progressive Lockout',
            AccountLockout::TYPE_PERMANENT   => 'Permanent Lockout',
            AccountLockout::TYPE_SOFT        => 'Soft Lockout (CAPTCHA Required)',
            default                          => 'Unknown',
        };
    }
}
