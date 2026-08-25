<section aria-labelledby="browser-game-accounts-heading">
    <h2 id="browser-game-accounts-heading">Accounts</h2>
    @if($message)<p role="status">{{ $message }}</p>@endif
    @forelse($accounts as $account)
        <article wire:key="account-{{ $account->getKey() }}">
            <h3>{{ $account->name }}</h3>
            <p>Status: {{ $account->status }}</p>
            @if($account->status === 'active')
                <button type="button" wire:click="suspend('{{ $account->getKey() }}')">Suspend account</button>
            @elseif($account->status === 'suspended')
                <button type="button" wire:click="reactivate('{{ $account->getKey() }}')">Reactivate account</button>
            @endif
            <form wire:submit="ban('{{ $account->getKey() }}')">
                <label>Ban reason <input wire:model="banReason" type="text" maxlength="1000" required></label>
                <label>Ban ends at <input wire:model="banEndsAt" type="datetime-local"></label>
                <button type="submit">Ban account</button>
            </form>
            @foreach($account->bans->whereNull('revoked_at') as $ban)
                <button type="button" wire:click="liftBan('{{ $account->getKey() }}', {{ $ban->getKey() }})">Lift ban</button>
            @endforeach
            <form wire:submit="updateIdentity('{{ $account->getKey() }}')">
                <label>Name <input wire:model="name" type="text" required></label>
                <label>Email <input wire:model="email" type="email"></label>
                <label>Username <input wire:model="username" type="text"></label>
                <button type="submit">Save identity</button>
            </form>
            <button type="button" wire:click="requestDeletion('{{ $account->getKey() }}')">Request account deletion</button>
            @if($account->privacy?->deletion_requested_at && !$account->privacy?->deletion_completed_at)
                <button type="button" wire:click="completeDeletion('{{ $account->getKey() }}')">Complete account deletion</button>
            @endif
            @if(!$account->email_verified_at)<button type="button" wire:click="verifyEmail('{{ $account->getKey() }}')">Verify email</button>@endif
            <button type="button" wire:click="revokeAllSessions('{{ $account->getKey() }}')">Revoke sessions</button>
            <button type="button" wire:click="issueRecovery('{{ $account->getKey() }}')">Issue recovery</button>
            <form wire:submit="updateAgeRegion('{{ $account->getKey() }}')">
                <label>Birth year <input wire:model="birthYear" type="number" min="1900"></label>
                <label>Region <input wire:model="region" type="text" maxlength="2"></label>
                <label><input wire:model="ageVerified" type="checkbox"> Age verified</label>
                <button type="submit">Save age and region</button>
            </form>
            <form wire:submit="updatePrivacy('{{ $account->getKey() }}')">
                <label>Visibility <select wire:model="profileVisibility"><option value="private">Private</option><option value="friends">Friends</option><option value="public">Public</option></select></label>
                <label><input wire:model="marketingConsent" type="checkbox"> Marketing</label>
                <label><input wire:model="analyticsConsent" type="checkbox"> Analytics</label>
                <button type="submit">Save privacy</button>
            </form>
        </article>
    @empty
        <p role="status">No accounts are available.</p>
    @endforelse
</section>
