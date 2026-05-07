<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Livewire;

use ArtisanPackUI\SecurityAuth\Http\Middleware\StepUpAuthentication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

class StepUpAuthenticationModal extends Component
{
    public bool $show = false;

    public string $method = 'password';

    public string $password = '';

    public string $code = '';

    public array $availableMethods = [];

    public ?string $redirectUrl = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->loadAvailableMethods();
    }

    #[On( 'step-up-required' )]
    public function openModal( ?string $redirectUrl = null ): void
    {
        $this->redirectUrl = $redirectUrl ?? session( 'step_up_intended_url' );
        $this->loadAvailableMethods();
        $this->reset( ['password', 'code', 'error'] );
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset( ['password', 'code', 'error'] );
    }

    public function loadAvailableMethods(): void
    {
        $user = Auth::user();

        if ( ! $user ) {
            return;
        }

        $this->availableMethods = ['password'];

        if ( method_exists( $user, 'hasTwoFactorEnabled' ) && $user->hasTwoFactorEnabled() ) {
            $this->availableMethods[] = '2fa';
        }

        if ( method_exists( $user, 'hasWebAuthnCredentials' ) && $user->hasWebAuthnCredentials() ) {
            $this->availableMethods[] = 'webauthn';
        }

        if ( method_exists( $user, 'hasPlatformAuthenticators' ) && $user->hasPlatformAuthenticators() ) {
            $this->availableMethods[] = 'biometric';
        }

        // Default to first available method
        if ( ! in_array( $this->method, $this->availableMethods ) ) {
            $this->method = $this->availableMethods[0] ?? 'password';
        }
    }

    public function verifyPassword(): void
    {
        $this->validate( [
            'password' => 'required|string',
        ] );

        $user = Auth::user();

        if ( ! $user ) {
            $this->error = 'You must be logged in.';

            return;
        }

        if ( ! Hash::check( $this->password, $user->password ) ) {
            $this->error    = 'Invalid password.';
            $this->password = '';

            return;
        }

        $this->completeStepUp();
    }

    public function verify2fa(): void
    {
        $this->validate( [
            'code' => 'required|string|size:6',
        ] );

        $user = Auth::user();

        if ( ! $user ) {
            $this->error = 'You must be logged in.';

            return;
        }

        // Use the TwoFactorService if available
        if ( method_exists( $user, 'validateTwoFactorCode' ) ) {
            if ( ! $user->validateTwoFactorCode( $this->code ) ) {
                $this->error = 'Invalid verification code.';
                $this->code  = '';

                return;
            }
        } else {
            $this->error = 'Two-factor authentication is not configured.';
            $this->code  = '';
            return;
        }

        $this->completeStepUp();
    }

    #[On( 'webauthn-step-up-complete' )]
    public function verifyWebAuthn( array $response ): void
    {
        // WebAuthn verification handled by the event
        $this->completeStepUp();
    }

    #[On( 'biometric-step-up-complete' )]
    public function verifyBiometric( array $response ): void
    {
        // Biometric verification handled by the event
        $this->completeStepUp();
    }

    public function getMethodLabel( string $method ): string
    {
        return match ( $method ) {
            'password'  => 'Password',
            '2fa'       => 'Authenticator App',
            'webauthn'  => 'Security Key',
            'biometric' => 'Biometric',
            default     => ucfirst( $method ),
        };
    }

    public function getMethodIcon( string $method ): string
    {
        return match ( $method ) {
            'password'  => 'fas fa-key',
            '2fa'       => 'fas fa-mobile-alt',
            'webauthn'  => 'fas fa-usb',
            'biometric' => 'fas fa-fingerprint',
            default     => 'fas fa-shield-alt',
        };
    }

    public function render()
    {
        return view( 'security::livewire.step-up-authentication-modal' );
    }

    protected function completeStepUp(): void
    {
        StepUpAuthentication::complete();

        $this->show = false;

        if ( $this->redirectUrl ) {
            $this->redirect( $this->redirectUrl);
        } else {
            $this->dispatch( 'step-up-complete');
        }
    }
}
