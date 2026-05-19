{{--
    Account lockout status banner.

    Plain HTML + Tailwind by design — this package does not depend on
    artisanpack-ui/livewire-ui-components.

    Renders the current lockout banner (when applicable) and a paginated
    history of past lockouts for the authenticated user.
--}}
<div class="space-y-4">
    @if ( $currentLockout )
        {{-- Active lockout banner --}}
        <div role="alert" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex items-start gap-3">
                <span class="text-red-500 text-xl" aria-hidden="true">⚠️</span>
                <div class="flex-1">
                    <h3 class="text-red-800 font-semibold">{{ __( 'Account locked' ) }}</h3>
                    <p class="text-red-700 text-sm mt-1">
                        {{ __( 'Reason' ) }}: {{ $currentLockout['reason'] ?? __( 'Security policy' ) }}
                    </p>
                    @if ( isset( $currentLockout['unlocks_at'] ) )
                        <p class="text-red-600 text-xs mt-1">
                            {{ __( 'Unlocks' ) }}: {{ $currentLockout['unlocks_at'] }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div role="status" class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
            <div class="flex items-center gap-2">
                <span class="text-green-600" aria-hidden="true">✓</span>
                <span class="text-green-800 text-sm">{{ __( 'Your account is not locked.' ) }}</span>
            </div>
        </div>
    @endif

    {{-- Lockout history --}}
    <div>
        <h3 class="text-base font-semibold mb-2">{{ __( 'Recent lockout history' ) }}</h3>
        @if ( $this->lockoutHistory->isEmpty() )
            <p class="text-sm text-gray-500">{{ __( 'No previous lockouts.' ) }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ __( 'When' ) }}</th>
                        <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ __( 'Reason' ) }}</th>
                        <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ __( 'Duration' ) }}</th>
                        <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ __( 'Status' ) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ( $this->lockoutHistory as $lockout )
                        <tr wire:key="lockout-{{ $lockout->id }}" class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $lockout->created_at?->diffForHumans() }}</td>
                            <td class="px-3 py-2">{{ $lockout->reason ?? __( '—' ) }}</td>
                            <td class="px-3 py-2">
                                @if ( $lockout->duration_minutes )
                                    {{ $lockout->duration_minutes }} {{ __( 'minutes' ) }}
                                @else
                                    {{ __( '—' ) }}
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ( $lockout->is_active ?? false )
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __( 'Active' ) }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ __( 'Cleared' ) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $this->lockoutHistory->links() }}
            </div>
        @endif
    </div>
</div>
