<?php

namespace App\Livewire;

use App\Events\GuildMemberJoined;
use App\Events\GuildMemberLeft;
use App\Models\Guild;
use App\Models\Player;
use Livewire\Component;

class GuildPanel extends Component
{
    public $player;

    public $playerGuilds = [];

    public $availableGuilds = [];

    public $selectedGuild = null;

    public $guildMembers = [];

    protected $listeners = [
        'guild-updated' => 'refreshGuilds',
    ];

    public function mount()
    {
        $this->loadGuilds();
    }

    public function loadGuilds()
    {
        $this->player = Player::with('guilds')->first();

        if (! $this->player) {
            $this->player = Player::create([
                'username' => 'Demo Player',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'level' => 1,
                'experience' => 0,
            ]);
        }

        // Load player's guilds
        $this->playerGuilds = $this->player->guilds->toArray();

        // Get guild IDs player is already in
        $playerGuildIds = $this->player->guilds->pluck('id');

        // Get available guilds
        $this->availableGuilds = Guild::whereNotIn('id', $playerGuildIds)->get()->toArray();

        // If a guild is selected, load its members
        if ($this->selectedGuild) {
            $this->loadGuildMembers($this->selectedGuild);
        }
    }

    public function selectGuild($guildId)
    {
        $this->selectedGuild = $guildId;
        $this->loadGuildMembers($guildId);
    }

    public function loadGuildMembers($guildId)
    {
        $guild = Guild::with(['members' => function ($query) {
            $query->orderBy('guild__memberships.joined_at', 'desc');
        }])->find($guildId);

        if ($guild) {
            $this->guildMembers = $guild->members->map(function (Player $member): array {
                return [
                    'id' => $member->id,
                    'username' => $member->username,
                    'level' => $member->level,
                    'role' => data_get($member->getRelationValue('pivot'), 'role', 'member'),
                    'joined_at' => data_get($member->getRelationValue('pivot'), 'joined_at'),
                ];
            })->toArray();
        }
    }

    public function joinGuild($guildId)
    {
        $guild = Guild::find($guildId);

        if (! $guild) {
            session()->flash('error', 'Guild not found!');

            return;
        }

        // Check if player is already in the guild
        if ($this->player->guilds()->where('guild_id', $guildId)->exists()) {
            session()->flash('error', 'You are already in this guild!');

            return;
        }

        // Join the guild
        $this->player->guilds()->attach($guildId, [
            'role' => 'member',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Broadcast guild member joined event
        event(new GuildMemberJoined($guild, $this->player));

        $this->loadGuilds();
        $this->dispatch('guild-joined', guildId: $guildId);
        session()->flash('success', "You joined {$guild->name}!");
    }

    public function leaveGuild($guildId)
    {
        $guild = Guild::find($guildId);

        if (! $guild) {
            session()->flash('error', 'Guild not found!');

            return;
        }

        // Leave the guild
        $this->player->guilds()->detach($guildId);

        // Broadcast guild member left event
        event(new GuildMemberLeft($guild, $this->player));

        // Clear selected guild if it was the one we left
        if ($this->selectedGuild == $guildId) {
            $this->selectedGuild = null;
            $this->guildMembers = [];
        }

        $this->loadGuilds();
        $this->dispatch('guild-left', guildId: $guildId);
        session()->flash('info', "You left {$guild->name}.");
    }

    public function refreshGuilds()
    {
        $this->loadGuilds();
    }

    public function render()
    {
        return view('livewire.guild-panel');
    }
}
