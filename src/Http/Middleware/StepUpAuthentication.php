<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StepUpAuthentication
{
    /**
     * The timeout for step-up authentication in minutes.
     */
    protected int $stepUpTimeout;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        $this->stepUpTimeout = config( 'artisanpack.security-auth.step_up_authentication.timeout_minutes', 15 );
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle( Request $request, Closure $next, ?string $method = null ): Response
    {
        $user = Auth::user();
        if ( ! $user ) {
            if ( $request->expectsJson() ) {
                return response()->json( ['error' => 'Unauthorized'], 401 );
            }

            return $this->redirectTo( 'login' );
        }

        // Check if step-up authentication is recent enough
        $lastStepUp = session( 'step_up_authenticated_at' );

        if ( ! $lastStepUp || now()->diffInMinutes( $lastStepUp ) > $this->stepUpTimeout ) {
            // Require step-up authentication
            session( ['step_up_intended_url' => $request->fullUrl()] );

            if ( $request->expectsJson() ) {
                return response()->json( [
                    'error'           => 'Step-up authentication required',
                    'require_step_up' => true,
                    'methods'         => $this->getAvailableMethods( $user, $method ),
                ], 401 );
            }

            return $this->redirectTo( 'password.confirm' )
                ->with( 'warning', 'Please confirm your identity to continue.' );
        }

        return $next( $request );
    }

    /**
     * Mark step-up authentication as complete.
     */
    public static function complete(): void
    {
        session( ['step_up_authenticated_at' => now()] );
    }

    /**
     * Clear step-up authentication.
     */
    public static function clear(): void
    {
        session()->forget( ['step_up_authenticated_at', 'step_up_intended_url'] );
    }

    /**
     * Redirect to a route, falling back to a URL if the route doesn't exist.
     */
    protected function redirectTo( string $routeName ): \Illuminate\Http\RedirectResponse
    {
        $configRoute = config( "artisanpack.security-auth.step_up_authentication.routes.{$routeName}" );

        if ( $configRoute ) {
            return redirect( $configRoute );
        }

        if ( \Illuminate\Support\Facades\Route::has( $routeName ) ) {
            return redirect()->route( $routeName );
        }

        // Fallback to home or return a response
        return redirect( '/' );
    }

    /**
     * Get available step-up authentication methods.
     *
     * @return array<string>
     */
    protected function getAvailableMethods( mixed $user, ?string $requiredMethod ): array
    {
        $methods = ['password'];

        // Check if user has 2FA enabled
        if ( method_exists( $user, 'hasTwoFactorEnabled' ) && $user->hasTwoFactorEnabled() ) {
            $methods[] = '2fa';
        }

        // Check if user has WebAuthn credentials
        if ( method_exists( $user, 'hasWebAuthnCredentials' ) && $user->hasWebAuthnCredentials() ) {
            $methods[] = 'webauthn';
        }

        // Check if user has biometric
        if ( method_exists( $user, 'hasPlatformAuthenticators' ) && $user->hasPlatformAuthenticators() ) {
            $methods[] = 'biometric';
        }

        // If a specific method is required, filter
        if ( $requiredMethod ) {
            $methods = in_array( $requiredMethod, $methods ) ? [$requiredMethod] : [];
        }

        return $methods;
    }
}
