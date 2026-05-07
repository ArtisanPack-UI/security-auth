<?php

declare( strict_types=1 );

use ArtisanPackUI\SecurityAuth\SecurityAuth;

it( 'binds the security-auth singleton', function (): void {
    expect( app( 'security-auth' ) )->toBeInstanceOf( SecurityAuth::class );
} );

it( 'returns the same instance on subsequent resolutions', function (): void {
    expect( app( 'security-auth' ) )->toBe( app( 'security-auth' ) );
} );

it( 'exposes the security_auth() helper', function (): void {
    expect( security_auth() )->toBeInstanceOf( SecurityAuth::class );
} );
