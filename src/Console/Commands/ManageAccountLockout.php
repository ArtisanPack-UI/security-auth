<?php

/**
 * `ManageAccountLockout` Artisan command.
 *
 * @package    ArtisanPack_UI
 * @subpackage SecurityAuth
 *
 * @author     Jacob Martella <support@artisanpackui.dev>
 *
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SecurityAuth\Console\Commands;

use ArtisanPackUI\SecurityAuth\Authentication\Lockout\AccountLockoutManager;
use ArtisanPackUI\SecurityAuth\Models\AccountLockout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ManageAccountLockout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:lockout
                            {action : The action to perform (list|unlock|lock)}
                            {--user= : User ID or email for user-specific actions}
                            {--ip= : IP address for IP-specific actions}
                            {--reason= : Reason for locking}
                            {--duration=60 : Lock duration in minutes}
                            {--permanent : Create a permanent lock}
                            {--all : Apply to all active lockouts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage account lockouts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument( 'action' );

        return match ( $action ) {
            'list'   => $this->listLockouts(),
            'unlock' => $this->unlockAccount(),
            'lock'   => $this->lockAccount(),
            default  => $this->invalidAction( $action ),
        };
    }

    protected function listLockouts(): int
    {
        $query = AccountLockout::active();

        if ( $userId = $this->option( 'user' ) ) {
            $user = $this->findUser( $userId );
            if ( ! $user ) {
                return self::FAILURE;
            }
            $query->where( 'user_id', $user->id );
        }

        if ( $ip = $this->option( 'ip' ) ) {
            $query->where( 'ip_address', $ip );
        }

        $lockouts = $query->get();

        if ( $lockouts->isEmpty() ) {
            $this->info( 'No active lockouts found.' );

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'User ID', 'IP Address', 'Type', 'Reason', 'Expires At'],
            $lockouts->map( fn ( $l ) => [
                $l->id,
                $l->user_id ?? '-',
                $l->ip_address ?? '-',
                $l->lockout_type,
                \Illuminate\Support\Str::limit( $l->reason, 30 ),
                $l->expires_at?->format( 'Y-m-d H:i:s' ) ?? 'Never',
            ] ),
        );

        return self::SUCCESS;
    }

    protected function unlockAccount(): int
    {
        /** @var AccountLockoutManager $lockoutManager */
        $lockoutManager = App::make( AccountLockoutManager::class );

        if ( $this->option( 'all' ) ) {
            // is_active is computed (see AccountLockout::isActive()) and not
            // a real column; unlocked_by expects an int FK, not a string.
            // Iterate so the model's unlock() contract is preserved.
            $count = 0;

            AccountLockout::active()->each( function ( AccountLockout $lockout ) use ( &$count ): void {
                $lockout->unlock( null, 'Unlocked via console command' );
                $count++;
            } );

            $this->info( "Unlocked {$count} account(s)." );

            return self::SUCCESS;
        }

        if ( $userId = $this->option( 'user' ) ) {
            $user = $this->findUser( $userId );
            if ( ! $user ) {
                return self::FAILURE;
            }

            $lockoutManager->unlockUser( $user );
            $this->info( "Unlocked account for user: {$user->email}" );

            return self::SUCCESS;
        }

        if ( $ip = $this->option( 'ip' ) ) {
            $lockoutManager->unlockIp( $ip );
            $this->info( "Unlocked IP address: {$ip}" );

            return self::SUCCESS;
        }

        $this->error( 'Please specify --user, --ip, or --all' );

        return self::FAILURE;
    }

    protected function lockAccount(): int
    {
        /** @var AccountLockoutManager $lockoutManager */
        $lockoutManager = App::make( AccountLockoutManager::class );

        $reason    = $this->option( 'reason' ) ?? 'Locked via console command';
        $permanent = (bool) $this->option( 'permanent' );

        if ( $permanent ) {
            $duration = null;
        } else {
            $duration = (int) $this->option( 'duration' );
            if ( $duration <= 0 ) {
                $this->error( '--duration must be a positive integer (or pass --permanent for an indefinite lockout).' );

                return self::FAILURE;
            }
        }

        $durationText = $permanent ? 'permanently' : "{$duration} minutes";

        if ( $userId = $this->option( 'user' ) ) {
            $user = $this->findUser( $userId );
            if ( ! $user ) {
                return self::FAILURE;
            }

            $lockoutManager->lockUser( $user, $duration, $reason );
            $this->info( "Locked account for user: {$user->email} ({$durationText})" );

            return self::SUCCESS;
        }

        if ( $ip = $this->option( 'ip' ) ) {
            $lockoutManager->lockIp( $ip, $duration, $reason );
            $this->info( "Locked IP address: {$ip} ({$durationText})" );

            return self::SUCCESS;
        }

        $this->error( 'Please specify --user or --ip' );

        return self::FAILURE;
    }

    protected function findUser( string $identifier ): mixed
    {
        $userModel = config( 'auth.providers.users.model' );
        $user      = $userModel::where( 'id', $identifier )
            ->orWhere( 'email', $identifier )
            ->first();

        if ( ! $user ) {
            $this->error( "User not found: {$identifier}" );

            return null;
        }

        return $user;
    }

    protected function invalidAction( string $action ): int
    {
        $this->error( "Invalid action: {$action}" );
        $this->line( 'Valid actions: list, unlock, lock' );

        return self::FAILURE;
    }
}
