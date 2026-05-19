{{--
    Password strength meter.

    Plain HTML + Tailwind by design — this package does not depend on
    artisanpack-ui/livewire-ui-components. Override this view in your app
    if you want richer UI components.

    The host form is expected to wire its own password input to the
    component's `wire:model="password"` — see usage examples in the docs.
--}}
<div class="space-y-2" role="status" aria-live="polite">
    @if ( filled( $password ) )
        {{-- Visual strength bar --}}
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div
                class="h-2 rounded-full transition-all duration-300 {{ match( $this->getProgressColor() ) {
                    'red'    => 'bg-red-500',
                    'orange' => 'bg-orange-500',
                    'yellow' => 'bg-yellow-500',
                    'green'  => 'bg-green-500',
                    default  => 'bg-gray-400',
                } }}"
                style="width: {{ $this->getBarWidth() }}%"
            ></div>
        </div>

        {{-- Label + crack time --}}
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium inline-flex items-center px-2 py-0.5 rounded {{ match( $this->getBadgeColor() ) {
                'red'    => 'bg-red-100 text-red-800',
                'orange' => 'bg-orange-100 text-orange-800',
                'yellow' => 'bg-yellow-100 text-yellow-800',
                'green'  => 'bg-green-100 text-green-800',
                default  => 'bg-gray-100 text-gray-800',
            } }}">
                {{ $label }}
            </span>
            @if ( $crackTime )
                <span class="text-xs text-gray-500">
                    {{ __( 'Estimated crack time' ) }}: {{ $crackTime }}
                </span>
            @endif
        </div>

        {{-- Feedback messages --}}
        @if ( ! empty( $feedback ) )
            <ul class="text-xs text-gray-600 space-y-0.5 list-disc list-inside">
                @foreach ( $feedback as $message )
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif

        {{-- Requirements checklist --}}
        @if ( ! empty( $requirements ) )
            <ul class="text-xs space-y-0.5">
                @foreach ( $requirements as $requirement )
                    <li class="flex items-center gap-1 {{ ( $requirement['met'] ?? false ) ? 'text-green-700' : 'text-gray-500' }}">
                        <span aria-hidden="true">{{ ( $requirement['met'] ?? false ) ? '✓' : '○' }}</span>
                        <span>{{ $requirement['label'] ?? $requirement['rule'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
