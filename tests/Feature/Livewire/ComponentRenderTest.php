<?php

declare( strict_types=1 );

use ArtisanPackUI\SecurityAuth\Livewire\AccountLockoutStatus;
use ArtisanPackUI\SecurityAuth\Livewire\PasswordStrengthMeter;
use ArtisanPackUI\SecurityAuth\Livewire\SessionManager;
use ArtisanPackUI\SecurityAuth\Livewire\StepUpAuthenticationModal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach( function (): void {
    // The package migrations alter a `users` table that doesn't exist in
    // the testbench by default. Stand up a minimal one before the package
    // migrations run.
    if ( ! Schema::hasTable( 'users' ) ) {
        Schema::create( 'users', function ( Blueprint $table ): void {
            $table->id();
            $table->string( 'name' );
            $table->string( 'email' )->unique();
            $table->timestamp( 'email_verified_at' )->nullable();
            $table->string( 'password' );
            $table->rememberToken();
            $table->timestamps();
        } );
    }

    $this->artisan( 'migrate' );

    $this->actingAs( new class extends Illuminate\Foundation\Auth\User {
        public $id = 1;

        protected $guarded = [];

        public function getAuthIdentifier()
        {
            return 1;
        }
    } );
} );

it( 'renders the PasswordStrengthMeter Livewire component', function (): void {
    Livewire::test( PasswordStrengthMeter::class )
        ->assertStatus( 200 );
} );

it( 'renders the AccountLockoutStatus Livewire component', function (): void {
    Livewire::test( AccountLockoutStatus::class )
        ->assertStatus( 200 );
} );

it( 'renders the SessionManager Livewire component', function (): void {
    Livewire::test( SessionManager::class )
        ->assertStatus( 200 );
} );

it( 'renders the StepUpAuthenticationModal Livewire component', function (): void {
    Livewire::test( StepUpAuthenticationModal::class )
        ->assertStatus( 200 );
} );
