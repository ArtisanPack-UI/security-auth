<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Livewire;

use ArtisanPackUI\SecurityAuth\Authentication\Session\AdvancedSessionManager;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SessionManager extends Component
{
    public array $sessions = [];

    public ?string $currentSessionId = null;

    public ?string $terminatingSessionId = null;

    protected AdvancedSessionManager $sessionManager;

    public function boot( AdvancedSessionManager $sessionManager ): void
    {
        $this->sessionManager = $sessionManager;
    }

    public function mount(): void
    {
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $user = Auth::user();

        if ( ! $user || ! method_exists( $user, 'userSessions' ) ) {
            return;
        }

        $currentSessionToken = session( 'user_session_token' );

        $this->sessions = $user->userSessions()
            ->active()
            ->orderBy( 'last_activity_at', 'desc' )
            ->get()
            ->map( function ( $session ) use ( $currentSessionToken ) {
                $isCurrent = $session->session_token === $currentSessionToken;

                if ( $isCurrent ) {
                    $this->currentSessionId = $session->id;
                }

                return [
                    'id'            => $session->id,
                    'ip_address'    => $session->ip_address,
                    'browser'       => $session->browser ?? 'Unknown',
                    'platform'      => $session->platform ?? 'Unknown',
                    'device_type'   => $session->device_type ?? 'desktop',
                    'device_icon'   => $this->getDeviceIcon( $session->device_type ?? 'desktop' ),
                    'location'      => $this->formatLocation( $session ),
                    'is_current'    => $isCurrent,
                    'started_at'    => $session->created_at->format( 'M j, Y g:i A' ),
                    'last_activity' => $session->last_activity_at?->diffForHumans() ?? 'Unknown',
                    'expires_at'    => $session->expires_at?->format( 'M j, Y g:i A' ),
                ];
            } )
            ->toArray();
    }

    public function confirmTerminate( string $sessionId ): void
    {
        $this->terminatingSessionId = $sessionId;
    }

    public function cancelTerminate(): void
    {
        $this->terminatingSessionId = null;
    }

    public function terminateSession( string $sessionId ): void
    {
        // Prevent terminating current session from here
        if ( $sessionId === $this->currentSessionId ) {
            session()->flash( 'error', 'Use the logout function to end your current session.' );
            $this->terminatingSessionId = null;

            return;
        }

        try {
            $this->sessionManager->terminateSession( $sessionId );
            session()->flash( 'success', 'Session has been terminated.' );
            $this->loadSessions();
        } catch ( Exception $e ) {
            session()->flash( 'error', 'Failed to terminate session: ' . $e->getMessage() );
        }

        $this->terminatingSessionId = null;
    }

    public function terminateAllOtherSessions(): void
    {
        $user = Auth::user();

        if ( ! $user ) {
            return;
        }

        try {
            $count = $this->sessionManager->terminateAllUserSessions(
                $user,
                except: $this->currentSessionId,
            );

            session()->flash( 'success', "{$count} session(s) have been terminated." );
            $this->loadSessions();
        } catch ( Exception $e ) {
            session()->flash( 'error', 'Failed to terminate sessions: ' . $e->getMessage() );
        }
    }

    public function render()
    {
        return view( 'security::livewire.session-manager' );
    }

    protected function getDeviceIcon( string $deviceType ): string
    {
        return match ( $deviceType ) {
            'desktop' => 'fas fa-desktop',
            'mobile'  => 'fas fa-mobile-alt',
            'tablet'  => 'fas fa-tablet-alt',
            default   => 'fas fa-globe',
        };
    }

    protected function formatLocation( $session ): string
    {
        $parts = array_filter( [
            $session->city,
            $session->region,
            $session->country,
        ]);

        return implode( ', ', $parts) ?: 'Unknown location';
    }
}
