<?php

namespace App\Filament\App\Pages;

use App\Models\Battle;
use App\Models\Npc;
use App\Models\Player;
use App\Services\ArenaService;
use App\Settings\GameSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

class BattleArena extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'Play & progress';

    protected static ?int $navigationSort = -5;

    protected string $view = 'filament.app.pages.battle-arena';

    #[Locked]
    public ?int $battleId = null;

    public string $opponentSearch = '';

    public function mount(): void
    {
        if (auth()->user()?->player) {
            $this->refreshArena();
        }
    }

    public function refreshArena(): void
    {
        app(ArenaService::class)->expireFor($this->player());
    }

    public function startNpc(int $npcId): void
    {
        $this->battleId = app(ArenaService::class)->startNpc($this->player(), $npcId)->id;
        $this->resetErrorBag();
    }

    public function challenge(int $playerId): void
    {
        $this->battleId = app(ArenaService::class)->challenge($this->player(), $playerId)->id;
        Notification::make()->title('Challenge sent')->body('Your opponent has 15 minutes to accept.')->success()->send();
    }

    public function respond(int $battleId, bool $accept): void
    {
        $this->battleId = app(ArenaService::class)->respond($this->player(), $battleId, $accept)->id;
        $this->resetErrorBag();
    }

    public function act(string $action, int $expectedTurn): void
    {
        abort_unless($this->battleId !== null, 404);
        $battle = app(ArenaService::class)->act($this->player(), $this->battleId, $action, $expectedTurn);
        $this->resetErrorBag();
        if ($battle->arena_status === 'completed') {
            Notification::make()->title('Battle complete')->body('Your result and combat log are saved below.')->success()->send();
        }
    }

    public function forfeit(): void
    {
        abort_unless($this->battleId !== null, 404);
        app(ArenaService::class)->forfeit($this->player(), $this->battleId);
    }

    public function rest(): void
    {
        app(ArenaService::class)->rest($this->player());
        Notification::make()->title('Rested at the shrine')->body('Health and mana fully restored.')->success()->send();
    }

    public function toggleOptIn(): void
    {
        $player = $this->player();
        app(ArenaService::class)->setOptIn($player, ! $player->arena_opt_in);
    }

    public function equip(int $itemId): void
    {
        app(ArenaService::class)->equip($this->player(), $itemId);
        Notification::make()->title('Equipment updated')->success()->send();
    }

    public function showBattle(int $battleId): void
    {
        $this->ownBattles($this->player())->findOrFail($battleId);
        $this->battleId = $battleId;
        $this->resetErrorBag();
    }

    protected function getViewData(): array
    {
        $player = auth()->user()?->player;
        if (! $player) {
            return ['player' => null];
        }
        $player->refresh();
        $current = $this->ownBattles($player)->whereIn('arena_status', ['pending', 'active'])->where('expires_at', '>', now())->latest('id')->first();
        $battle = $this->battleId ? $this->ownBattles($player)->with(['attacker:id,username', 'defender:id,username'])->findOrFail($this->battleId) : $current;
        if ($battle && $this->battleId === null) {
            $this->battleId = $battle->id;
        }
        $opponents = Player::query()->select(['id', 'username', 'level', 'arena_rating'])->where('arena_opt_in', true)->whereKeyNot($player->id)
            ->whereBetween('level', [max(1, $player->level - config('arena.level_gap')), $player->level + config('arena.level_gap')])
            ->when($this->opponentSearch !== '', fn ($query) => $query->where('username', 'like', '%'.mb_substr($this->opponentSearch, 0, 80).'%'))
            ->orderByDesc('arena_rating')->limit(12)->get();
        $npcs = Npc::where('is_active', true)->with('lootItem')->orderBy('level')->orderBy('id')->paginate(12, ['*'], 'npcPage');
        $equipment = $player->equipment()->with('item')->get()->keyBy('slot');
        $inventory = $player->items()->whereNotNull('slot')->orderBy('min_level')->get();
        $recent = $this->ownBattles($player)->with(['attacker:id,username', 'defender:id,username'])->latest('id')->limit(8)->get();
        $gold = (int) ($player->resources()->where('resource_type', 'gold')->value('quantity') ?? 0);
        $pvpEnabled = app(GameSettings::class)->pvp_enabled;

        return compact('player', 'current', 'battle', 'opponents', 'npcs', 'equipment', 'inventory', 'recent', 'gold', 'pvpEnabled');
    }

    private function player(): Player
    {
        abort_unless(auth()->check(), 403);

        return Player::where('email', auth()->user()->email)->firstOrFail();
    }

    /** @return Builder<Battle> */
    private function ownBattles(Player $player): Builder
    {
        return Battle::whereNotNull('arena_status')->where(fn ($query) => $query->where('attacker_id', $player->id)->orWhere('defender_id', $player->id));
    }
}
