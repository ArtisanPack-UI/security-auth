{{--
    Step-up authentication modal.

    Plain HTML + Tailwind by design — this package does not depend on
    artisanpack-ui/livewire-ui-components.

    Renders only when $show is true. The host app dispatches `open-step-up`
    (with optional redirect URL) to open it, or calls `openModal()` directly.
--}}
<div>
    @if ( $show )
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="step-up-title"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>

            {{-- Modal panel --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-4">
                        <h2 id="step-up-title" class="text-lg font-bold">
                            {{ __( 'Confirm your identity' ) }}
                        </h2>
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600"
                            aria-label="{{ __( 'Close' ) }}"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 mb-4">
                        {{ __( 'For your security, please verify your identity to continue.' ) }}
                    </p>

                    {{-- Method selector --}}
                    @if ( count( $availableMethods ) > 1 )
                        <div class="mb-4">
                            <label for="step-up-method" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __( 'Verification method' ) }}
                            </label>
                            <select
                                id="step-up-method"
                                wire:model.live="method"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                                @foreach ( $availableMethods as $availableMethod )
                                    <option value="{{ $availableMethod }}">{{ $this->getMethodLabel( $availableMethod ) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Error --}}
                    @if ( $error )
                        <div role="alert" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded p-3 mb-4">
                            {{ $error }}
                        </div>
                    @endif

                    {{-- Method-specific form --}}
                    @if ( $method === 'password' )
                        <form wire:submit.prevent="verifyPassword">
                            <label for="step-up-password" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __( 'Password' ) }}
                            </label>
                            <input
                                id="step-up-password"
                                type="password"
                                wire:model="password"
                                autocomplete="current-password"
                                autofocus
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            />
                            <div class="mt-4 flex justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-sm font-medium rounded-md"
                                >
                                    {{ __( 'Cancel' ) }}
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                >
                                    {{ __( 'Verify' ) }}
                                </button>
                            </div>
                        </form>
                    @elseif ( $method === '2fa' )
                        <form wire:submit.prevent="verify2fa">
                            <label for="step-up-code" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __( 'Two-factor code' ) }}
                            </label>
                            <input
                                id="step-up-code"
                                type="text"
                                wire:model="code"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]*"
                                autofocus
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm tracking-widest text-center"
                            />
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-sm font-medium rounded-md">
                                    {{ __( 'Cancel' ) }}
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                                    {{ __( 'Verify' ) }}
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- WebAuthn / biometric — JS-driven, host app supplies the credential and dispatches verifyWebAuthn / verifyBiometric --}}
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-600 mb-4">
                                {{ __( 'Please use your authenticator to verify.' ) }}
                            </p>
                            <div class="text-xs text-gray-500">
                                {{ __( 'Awaiting authenticator response…' ) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
