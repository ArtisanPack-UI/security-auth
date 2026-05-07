<?php

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Services;

use ArtisanPackUI\SecurityAuth\Contracts\BreachCheckerInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HaveIBeenPwnedService implements BreachCheckerInterface
{
    /**
     * The HaveIBeenPwned API URL.
     */
    protected const API_URL = 'https://api.pwnedpasswords.com/range/';

    /**
     * Check if a password has been exposed in known data breaches.
     *
     * Uses k-Anonymity model - only first 5 chars of SHA1 hash are sent.
     *
     * @param  string  $password  The plain-text password to check
     *
     * @return int Number of times password has been seen in breaches
     */
    public function check( string $password ): int
    {
        if ( ! config( 'artisanpack.security-auth.passwordSecurity.breachChecking.enabled', true ) ) {
            return 0;
        }

        $sha1   = strtoupper( sha1( $password ) );
        $prefix = substr( $sha1, 0, 5 );
        $suffix = substr( $sha1, 5 );

        // Cache::remember() reruns the loader whenever the stored value is null,
        // so an API outage would leave every password check waiting on a fresh
        // 5-second HTTP timeout. Read-then-write so we only cache successes,
        // and fail-cache outages briefly to avoid hammering a degraded API.
        if ( config( 'artisanpack.security-auth.passwordSecurity.breachChecking.cacheResults', true ) ) {
            $cacheKey     = "hibp_prefix_{$prefix}";
            $failCacheKey = "hibp_fail_{$prefix}";
            $ttl          = config( 'artisanpack.security-auth.passwordSecurity.breachChecking.cacheTtl', 86400 );

            if ( Cache::has( $failCacheKey ) ) {
                return 0;
            }

            $results = Cache::get( $cacheKey );

            if ( null === $results ) {
                $results = $this->fetchFromApi( $prefix );

                if ( null === $results ) {
                    Cache::put( $failCacheKey, true, 60 );
                } else {
                    Cache::put( $cacheKey, $results, $ttl );
                }
            }
        } else {
            $results = $this->fetchFromApi( $prefix );
        }

        if ( null === $results ) {
            // API failed, fail open (don't block user)
            return 0;
        }

        // Search for our suffix in the results
        foreach ( explode( "\n", $results ) as $line ) {
            $line = trim( $line );
            if ( empty( $line ) ) {
                continue;
            }

            $parts = explode( ':', $line );
            if ( 2 !== count( $parts ) ) {
                continue;
            }

            [$hashSuffix, $count] = $parts;

            if ( strtoupper( $hashSuffix ) === $suffix ) {
                return (int) $count;
            }
        }

        return 0;
    }

    /**
     * Check if password is compromised.
     */
    public function isCompromised( string $password ): bool
    {
        return $this->check( $password ) > 0;
    }

    /**
     * Fetch hash range from HIBP API.
     */
    protected function fetchFromApi( string $prefix ): ?string
    {
        try {
            $timeout = config( 'artisanpack.security-auth.passwordSecurity.breachChecking.apiTimeout', 5 );

            $response = Http::timeout( $timeout )
                ->withHeaders( [
                    'User-Agent'  => 'ArtisanPack-Security-Laravel-Package',
                    'Add-Padding' => 'true', // Enable padding for privacy
                ] )
                ->get( self::API_URL . $prefix );

            if ( $response->successful() ) {
                return $response->body();
            }

            Log::warning( 'HIBP API returned non-success status', [
                'status' => $response->status(),
                'prefix' => $prefix,
            ] );

            return null;
        } catch ( Exception $e ) {
            Log::error( 'HIBP API request failed', [
                'error'  => $e->getMessage(),
                'prefix' => $prefix,
            ] );

            return null;
        }
    }
}
