<section aria-labelledby="browser-game-economy-heading">
    <h2 id="browser-game-economy-heading">Economy</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    <h3>Wallets</h3>
    @forelse($wallets as $wallet)<p>{{ $wallet->currency_code }}: {{ $wallet->balance }}</p>@empty<p role="status">No balances are available.</p>@endforelse
    <form wire:submit="transfer">
        <input wire:model="recipientId" placeholder="Recipient" required>
        <input wire:model="currencyCode" placeholder="Currency" required>
        <input wire:model="quantity" type="number" min="1" required>
        <button type="submit">Transfer</button>
    </form>
    <h3>Marketplace</h3>
    <form wire:submit="createListing">
        <input wire:model="itemKey" placeholder="Item key" required>
        <input wire:model="currencyCode" placeholder="Currency" required>
        <input wire:model="quantity" type="number" min="1" required>
        <input wire:model="unitPrice" type="number" min="1" required>
        <button type="submit">Create listing</button>
    </form>
    @forelse($listings as $listing)
        <article wire:key="listing-{{ $listing->id }}">
            <p>{{ $listing->item_key }} × {{ $listing->quantity }} for {{ $listing->unit_price }} {{ $listing->currency_code }}</p>
            @if($listing->seller_id === (string) auth()->id())
                <button type="button" wire:click="cancel({{ $listing->id }})">Cancel</button>
            @else
                <button type="button" wire:click="purchase({{ $listing->id }})">Purchase</button>
            @endif
        </article>
    @empty<p role="status">No marketplace listings are available.</p>@endforelse
</section>
