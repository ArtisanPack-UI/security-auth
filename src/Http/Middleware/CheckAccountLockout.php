<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Http\Middleware;

use ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager;
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountLockout
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected AccountLockoutManager $lockoutManager,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle( Request $request, Closure $next ): Response
    {
        if ( ! config( 'artisanpack.security-auth.account_lockout.enabled', true ) ) {
            return $next( $request );
        }

        $user = Auth::user();
        $ip   = $request->ip();

        // Check IP lockout
        if ( $this->lockoutManager->isIpLocked( $ip ) ) {
            $lockout = $this->lockoutManager->getIpLockout( $ip );

            return $this->handleLockout( $request, $lockout, 'ip' );
        }

        // Check user lockout
        if ( $user && $this->lockoutManager->isUserLocked( $user ) ) {
            $lockout = $this->lockoutManager->getUserLockout( $user );

            return $this->handleLockout( $request, $lockout, 'user' );
        }

        return $next( $request );
    }

    /**
     * Handle a lockout response.
     */
    protected function handleLockout( Request $request, ?AccountLockout $lockout, string $type ): Response
    {
        if ( ! $lockout ) {
            return response()->json( ['error' => 'Account is locked'], 423 );
        }

        $remainingSeconds = $this->lockoutManager->getRemainingLockoutDuration( $lockout );
        $remainingMinutes = ceil( $remainingSeconds / 60 );

        $message = match ( $lockout->lockout_type ) {
            AccountLockout::TYPE_PERMANENT => 'Your account has been permanently locked. Please contact support.',
            AccountLockout::TYPE_SOFT      => 'Additional verification is required to proceed.',
            default                        => "Too many failed attempts. Please try again in {$remainingMinutes} minute(s).",
        };

        // For soft lockout, require CAPTCHA
        if ( $lockout->isSoft() ) {
            session( ['require_captcha' => true, 'lockout_id' => $lockout->id] );

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'error'           => $message,
                    'require_captcha' => true,
                    'lockout_type'    => $lockout->lockout_type,
                ], 423 );
            }

            // Continue but with CAPTCHA requirement
            return redirect()->back()
                ->with( 'warning', 'Please complete the CAPTCHA to continue.' );
        }

        // Log out user if they're logged in
        if ( Auth::check() && 'user' === $type ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ( $request->expectsJson() ) {
            return response()->json( [
                'error'             => $message,
                'lockout_type'      => $lockout->lockout_type,
                'remaining_seconds' => $remainingSeconds,
                'reason'            => $lockout->reason,
            ], 423 );
        }

        $redirect = Route::has( 'login' )
            ? redirect()->route( 'login' )
            : redirect()->to( url( '/' ) );

        return $redirect->with( 'error', $message );
    }
}
