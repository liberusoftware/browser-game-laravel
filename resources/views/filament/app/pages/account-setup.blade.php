<x-filament-panels::page>
    <div class="mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-8">
            <div class="mb-8 flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Welcome, {{ auth()->user()->name }}</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">A few quick choices will prepare your workspace, team context, and optional integrations. You can change these later from your account settings.</p>
                </div>
            </div>

            <form wire:submit="save">
                {{ $this->form }}
            </form>

            @if ($newApiToken)
                <div class="mt-6 rounded-xl border border-success-200 bg-success-50 p-4 text-sm dark:border-success-400/20 dark:bg-success-400/10">
                    <p class="font-semibold text-success-700 dark:text-success-300">Your API token was created</p>
                    <code class="mt-2 block break-all rounded-lg bg-white/70 p-3 text-xs dark:bg-black/20">{{ $newApiToken }}</code>
                    <p class="mt-2 text-success-700/80 dark:text-success-300/80">Copy it now. For your security, it will not be shown again.</p>
                    <div class="mt-4">
                        <x-filament::button wire:click="continueToApp" color="success" size="sm">
                            Continue to your workspace
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/[.03]">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">What is ready</p>
            <div class="mt-4 grid gap-4 text-sm">
                @foreach ([['Account', 'Profile and security live'], ['Team', 'Members and permissions are scoped'], ['OAuth', 'GitHub, Google, Facebook and X are available when configured'], ['API access', 'Sanctum tokens work with the game API']] as [$label, $description])
                    <div class="flex gap-3">
                        <x-filament::icon icon="heroicon-m-check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-success-500" />
                        <div><p class="font-medium text-gray-900 dark:text-white">{{ $label }}</p><p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $description }}</p></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Optional connections</p>
                <div class="mt-3 grid gap-2">
                    @foreach (['github' => 'GitHub', 'google' => 'Google', 'facebook' => 'Facebook', 'twitter-oauth-2' => 'X / Twitter'] as $provider => $label)
                        @php($configured = filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret")))
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-xs dark:bg-white/5">
                            <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            @if (auth()->user()->hasTokenFor($provider))
                                <span class="font-medium text-success-600 dark:text-success-400">Connected</span>
                            @elseif ($configured)
                                <a class="font-medium text-primary-600 hover:underline dark:text-primary-400" href="{{ route('oauth.redirect', ['provider' => $provider]) }}">Connect</a>
                            @else
                                <span class="text-gray-400">Not configured</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">OAuth client credentials are configured by the deployment administrator. You only approve access with the provider; secrets are never stored or requested here.</p>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
