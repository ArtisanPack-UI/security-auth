<?php

declare( strict_types=1 );

use ArtisanPackUI\SecurityAuth\SecurityAuth;

it( 'instantiates the SecurityAuth class', function (): void {
    expect( new SecurityAuth() )->toBeInstanceOf( SecurityAuth::class );
} );

it( 'reports its current version', function (): void {
    expect( ( new SecurityAuth() )->version() )->toBeString();
} );
