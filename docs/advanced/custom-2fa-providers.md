---
title: Custom 2FA Providers
---

# Custom 2FA Providers

Implement `TwoFactorProvider` to add a new verification method (SMS, hardware key challenge wrappers, custom OTP service, etc.):

```php
namespace ArtisanPackUI\SecurityAuth\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TwoFactorProvider
{
    public function getName(): string;
    public function sendChallenge( Authenticatable $user ): void;
    public function verify( Authenticatable $user, string $code ): bool;
}
```

## Example: SMS via Twilio

```php
namespace App\SecurityAuth\TwoFactor;

use ArtisanPackUI\SecurityAuth\TwoFactor\Contracts\TwoFactorProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Twilio\Rest\Client;

class SmsProvider implements TwoFactorProvider
{
    public function __construct(
        protected Client $twilio,
        protected string $fromNumber,
    ) {}

    public function getName(): string
    {
        return 'sms';
    }

    public function sendChallenge( Authenticatable $user ): void
    {
        $code = str_pad( (string) random_int( 0, 999_999 ), 6, '0', STR_PAD_LEFT );

        Cache::put( $this->cacheKey( $user ), $code, now()->addMinutes( 5 ) );

        $this->twilio->messages->create( $user->phone_number, [
            'from' => $this->fromNumber,
            'body' => "Your verification code: {$code}",
        ] );
    }

    public function verify( Authenticatable $user, string $code ): bool
    {
        $expected = Cache::pull( $this->cacheKey( $user ) );

        return $expected !== null && hash_equals( $expected, trim( $code ) );
    }

    protected function cacheKey( Authenticatable $user ): string
    {
        return "2fa_sms:{$user->getAuthIdentifier()}";
    }
}
```

## Registering the provider

```php
use ArtisanPackUI\SecurityAuth\TwoFactor\TwoFactorManager;
use App\SecurityAuth\TwoFactor\SmsProvider;
use Twilio\Rest\Client;

$this->app->afterResolving( TwoFactorManager::class, function ( TwoFactorManager $manager ): void {
    $manager->extend( new SmsProvider(
        twilio: new Client( config('services.twilio.sid'), config('services.twilio.token') ),
        fromNumber: config('services.twilio.from'),
    ) );
} );
```

Now usable:

```php
TwoFactor::provider('sms')->sendChallenge( $user );
TwoFactor::provider('sms')->verify( $user, $code );
```

To make SMS the default:

```php
'two_factor' => ['default_provider' => 'sms'],
```

## Conventions

- **Don't store secrets in plaintext.** If your provider keeps state (codes, salts, etc.), use `Cache` with a TTL, not the DB.
- **Hash-compare codes.** `hash_equals` over `===` to avoid timing attacks.
- **Single-use codes.** `Cache::pull()` rather than `Cache::get()` so a verified code can't be replayed.
- **Rate limit `sendChallenge`.** Each call costs you money (Twilio, etc.) and a stream of requests could be abused. Wrap with Laravel's `RateLimiter`.
