<div class="flex flex-col p-4 rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden px-4 py-5 bg-white sm:gap-4 sm:px-6"
    x-data="{ isLoading: @entangle('isLoading') }">

    <p class="font-semibold tracking-tight group flex items-center gap-2 text-lg">{{ ctrans('texts.payment_methods') }}
    </p>

    <svg id="spinner" wire:loading class="animate-spin h-5 w-5 text-primary"
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>

    @unless($isLoading && !(isset($company->settings->lendrose_bnpl_url) && $company->settings->lendrose_bnpl_url))
        <div class="my-3 flex flex-col space-y-3">
            @foreach($methods as $index => $method)
                <button wire:loading.remove
                    class="payment-method flex px-4 py-3 border rounded-lg lg:-mb-1 hover:shadow-sm transition duration-300"
                    wire:click="handleSelect('{{ $method['company_gateway_id'] }}', '{{ $method['gateway_type_id'] }}', '{{ $amount }}')">
                    <span>{{ $method['label'] }}</span>
                </button>
            @endforeach
            
            @if(isset($company->settings->lendrose_bnpl_url) && $company->settings->lendrose_bnpl_url)
                <a href="{{ $company->settings->lendrose_bnpl_url }}" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="flex px-4 py-3 border rounded-lg lg:-mb-1 hover:shadow-sm transition duration-300">
                    <span class="font-medium">Lendrose Buy Now Pay Later</span>
                </a>
            @endif
        </div>
    @endunless 

    @livewireStyles
    @livewireScripts
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Payment method script loaded');
            console.log('DOM ready, checking for buttons...');
            console.log('Window.Livewire available:', !!window.Livewire);
            console.log('Document ready state:', document.readyState);
            
            // Check if Livewire scripts are loaded
            const livewireScripts = document.querySelectorAll('script[src*="livewire"]');
            console.log('Livewire scripts found:', livewireScripts.length);
            livewireScripts.forEach((script, index) => {
                console.log(`Livewire script ${index}:`, script.src);
            });
            
            // Check for Livewire script config
            const livewireConfig = document.querySelector('script[data-navigate-once]');
            console.log('Livewire config found:', !!livewireConfig);
            
            function attachButtonListeners() {
                const buttons = document.querySelectorAll('.payment-method');
                console.log('Found buttons:', buttons.length);
                console.log('Buttons:', buttons);

                buttons.forEach((button, index) => {
                    console.log(`Button ${index}:`, button);
                    console.log(`Button ${index} wire:click:`, button.getAttribute('wire:click'));
                    console.log(`Button ${index} classes:`, button.className);
                    
                    button.addEventListener('click', (event) => {
                        console.log('Payment method button clicked');
                        console.log('Button element:', event.currentTarget);
                        console.log('Button wire:click attribute:', event.currentTarget.getAttribute('wire:click'));
                        
                        // Check if Livewire is available
                        if (window.Livewire) {
                            console.log('Livewire is available, trying to find component');
                            const component = window.Livewire.find(event.currentTarget.closest('[wire\\:id]')?.getAttribute('wire:id'));
                            console.log('Livewire component:', component);
                        } else {
                            console.log('Livewire not available');
                        }
                        
                        // Hide all buttons except the clicked one
                        buttons.forEach(btn => {
                            if (btn !== event.currentTarget) {
                                btn.style.display = 'none';
                            } else {
                                // Disable the clicked button
                                btn.disabled = true;

                                // Show the spinner by removing the 'hidden' class
                                const spinner = btn.querySelector('svg');
                                if (spinner) {
                                    spinner.classList.remove('hidden');
                                }

                                const span = btn.querySelector('span');
                                if (span) {
                                    span.style.display = 'none';
                                }
                            }
                        });
                    });
                });
            }

            // Attach listeners immediately
            attachButtonListeners();

            // Try to attach Livewire listeners if available
            function tryLivewireListeners() {
                if (window.Livewire) {
                    console.log('Livewire is now available');
                    
                    Livewire.on('loadingCompleted', () => {
                        console.log('Loading completed event received');
                        if (typeof isLoading !== 'undefined') {
                            isLoading = false;
                        }
                    });

                    Livewire.hook('morph.updated', ({ component, el }) => {
                        console.log('Livewire morph updated, re-attaching listeners');
                        attachButtonListeners();
                    });
                } else {
                    console.log('Livewire still not available, retrying...');
                    setTimeout(tryLivewireListeners, 500);
                }
            }

            // Try Livewire listeners after a delay
            setTimeout(tryLivewireListeners, 1000);
        });
    </script>
</div>
