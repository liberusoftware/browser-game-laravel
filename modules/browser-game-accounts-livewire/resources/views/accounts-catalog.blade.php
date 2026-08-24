<section aria-labelledby="browser-game-accounts-heading">
    <h2 id="browser-game-accounts-heading">Accounts</h2>
    @forelse($accounts as $account)
        <article wire:key="account-{{ $account->getKey() }}">
            <h3>{{ $account->name }}</h3>
            <p>Status: {{ $account->status }}</p>
            <form wire:submit="updateIdentity('{{ $account->getKey() }}')">
                <label>Name <input wire:model="name" type="text" required></label>
                <label>Email <input wire:model="email" type="email"></label>
                <label>Username <input wire:model="username" type="text"></label>
                <button type="submit">Save identity</button>
            </form>
            <button type="button" wire:click="requestDeletion('{{ $account->getKey() }}')">Request account deletion</button>
        </article>
    @empty
        <p role="status">No accounts are available.</p>
    @endforelse
</section>
