{{--
    Session manager — lists active sessions and offers terminate controls.

    Plain HTML + Tailwind by design — this package does not depend on
    artisanpack-ui/livewire-ui-components.
--}}
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold">{{ __( 'Active sessions' ) }}</h2>
        @if ( count( $sessions ) > 1 )
            <button
                type="button"
                wire:click="terminateAllOtherSessions"
                wire:confirm="{{ __( 'Sign out of all other sessions?' ) }}"
                class="text-sm text-red-600 hover:text-red-800 font-medium"
            >
                {{ __( 'Sign out of all other sessions' ) }}
            </button>
        @endif
    </div>

    @if ( empty( $sessions ) )
        <p class="text-sm text-gray-500">{{ __( 'No active sessions.' ) }}</p>
    @else
        <div class="space-y-2">
            @foreach ( $sessions as $session )
                @php
                    $isCurrent = ( $session['id'] ?? null ) === $currentSessionId;
                    $isTerminating = ( $session['id'] ?? null ) === $terminatingSessionId;
                @endphp
                <div
                    wire:key="session-{{ $session['id'] }}"
                    class="border rounded-lg p-4 {{ $isCurrent ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200' }}"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">
                                    {{ $session['device'] ?? __( 'Unknown device' ) }}
                                </span>
                                @if ( $isCurrent )
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ __( 'This session' ) }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 space-y-0.5">
                                @if ( isset( $session['ip_address'] ) )
                                    <div>
                                        <span class="font-medium">{{ __( 'IP' ) }}:</span>
                                        <span class="font-mono">{{ $session['ip_address'] }}</span>
                                    </div>
                                @endif
                                @if ( isset( $session['location'] ) )
                                    <div>
                                        <span class="font-medium">{{ __( 'Location' ) }}:</span>
                                        {{ $session['location'] }}
                                    </div>
                                @endif
                                @if ( isset( $session['last_activity'] ) )
                                    <div>
                                        <span class="font-medium">{{ __( 'Last active' ) }}:</span>
                                        {{ $session['last_activity'] }}
                                    </div>
                                @endif
                                @if ( isset( $session['auth_method'] ) )
                                    <div class="text-xs text-gray-500">
                                        {{ __( 'Signed in via' ) }}: {{ $session['auth_method'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            @if ( $isTerminating )
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        wire:click="terminateSession({{ json_encode($session['id']) }})"
                                        class="text-xs px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded font-medium"
                                    >
                                        {{ __( 'Confirm sign out' ) }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancelTerminate"
                                        class="text-xs px-3 py-1.5 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded font-medium"
                                    >
                                        {{ __( 'Cancel' ) }}
                                    </button>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="confirmTerminate({{ json_encode($session['id']) }})"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium"
                                    aria-label="{{ $isCurrent ? __( 'Sign out of this current session' ) : __( 'Sign out of this session' ) }}"
                                >
                                    {{ $isCurrent ? __( 'Sign out this session' ) : __( 'Sign out' ) }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
