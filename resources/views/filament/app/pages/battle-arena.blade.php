<x-filament-panels::page>
    @unless ($player)
        <x-filament::section heading="Your legend begins with a character" description="Finish account setup to choose your class and receive a starter kit.">
            <x-filament::button tag="a" :href="\App\Filament\App\Pages\AccountSetup::getUrl(panel: 'app')">Prepare my character</x-filament::button>
        </x-filament::section>
    @else
        <div wire:poll.5s="refreshArena" class="space-y-6">
            <section class="adventure-hero" aria-labelledby="arena-heading">
                <div class="adventure-hero-copy">
                    <p class="adventure-eyebrow">Steel. Strategy. Spellcraft.</p>
                    <h2 id="arena-heading">The Dawnvale Battle Arena</h2>
                    <p>Choose your equipment, read your opponent, and make every turn count. Hunt NPCs for treasure or invite another adventurer to a tactical duel.</p>
                    <div class="adventure-actions">
                        <x-filament::button wire:click="rest" wire:loading.attr="disabled" :disabled="(bool) $current" icon="heroicon-o-heart">Rest at shrine · Free</x-filament::button>
                        <x-filament::button wire:click="toggleOptIn" wire:loading.attr="disabled" :disabled="(bool) $current" color="gray">{{ $player->arena_opt_in ? 'Leave PvP matchmaking' : 'Opt in to PvP challenges' }}</x-filament::button>
                    </div>
                </div>
                <div class="adventure-level">
                    <strong>Level {{ $player->level }}</strong>
                    <span>{{ $player->health }} / {{ $player->max_health }} health · {{ $player->mana }} / {{ $player->max_mana }} mana</span>
                    <span>{{ number_format($gold) }} gold · Rating {{ $player->arena_rating }}</span>
                    <span>{{ $player->arena_opt_in ? 'Open to player challenges' : 'NPC adventures only' }}</span>
                </div>
            </section>
            @error('arena')<p class="arena-feedback" role="alert">{{ $message }}</p>@enderror

            @if ($current && $battle?->id !== $current->id)
                <x-filament::button wire:click="showBattle({{ $current->id }})" color="warning">Return to current battle</x-filament::button>
            @endif

            @if ($battle)
                <x-filament::section heading="Battle #{{ $battle->id }}" :description="ucfirst($battle->arena_status).' · '.($battle->battle_type === 'pvp' ? 'Player duel' : $battle->opponent_name)">
                    @if ($battle->arena_status === 'pending')
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $battle->attacker->username }} has challenged {{ $battle->defender->username }}. Expires {{ $battle->expires_at->diffForHumans() }}.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            @if ($battle->defender_id === $player->id)
                                <x-filament::button wire:click="respond({{ $battle->id }}, true)" wire:loading.attr="disabled">Accept duel</x-filament::button>
                                <x-filament::button wire:click="respond({{ $battle->id }}, false)" wire:loading.attr="disabled" color="gray">Decline</x-filament::button>
                            @else
                                <x-filament::button wire:click="forfeit" wire:loading.attr="disabled" color="gray">Withdraw challenge</x-filament::button>
                            @endif
                        </div>
                    @elseif ($battle->arena_state)
                        @php($state = $battle->arena_state)
                        <div class="arena-fighters">
                            @foreach (['attacker', 'defender'] as $side)
                                @php($fighter = $state[$side])
                                <article class="arena-fighter" wire:key="fighter-{{ $battle->id }}-{{ $side }}">
                                    <p class="arena-eyebrow">{{ ($side === 'attacker' ? $battle->attacker_id : $battle->defender_id) === $player->id ? 'You' : 'Opponent' }}</p>
                                    <h3>{{ $fighter['name'] }}</h3>
                                    <div class="arena-meter-label"><span>Health</span><span>{{ $fighter['hp'] }} / {{ $fighter['health'] }}</span></div>
                                    <progress class="arena-health" value="{{ $fighter['hp'] }}" max="{{ $fighter['health'] }}" aria-label="{{ $fighter['name'] }} health"></progress>
                                    <div class="arena-meter-label"><span>Mana</span><span>{{ $fighter['mp'] }} / {{ $fighter['mana'] }}</span></div>
                                    <progress class="arena-mana" value="{{ $fighter['mp'] }}" max="{{ max(1, $fighter['mana']) }}" aria-label="{{ $fighter['name'] }} mana"></progress>
                                    @if ($fighter['guarding'])<x-filament::badge color="info">Guarding · next hit reduced</x-filament::badge>@endif
                                </article>
                            @endforeach
                        </div>
                        @if ($battle->arena_status === 'active')
                            <div class="mt-4" role="status" aria-live="polite">
                                <p class="font-semibold">{{ $battle->turn_player_id === $player->id ? 'Your move' : 'Waiting for your opponent' }} · Turn {{ $battle->arena_turn + 1 }} / {{ config('arena.max_turns') }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Higher agility acts first in PvP. Guard restores mana; arcane attacks pierce armor. Turns expire after {{ config('arena.turn_minutes') }} minutes.</p>
                            </div>
                            @if ($battle->turn_player_id === $player->id)
                                @php($mySide = $battle->attacker_id === $player->id ? 'attacker' : 'defender')
                                <div class="arena-action-grid">
                                    @foreach (config('arena.actions') as $key => $action)
                                        <button type="button" class="arena-action" wire:key="action-{{ $key }}" wire:click="act('{{ $key }}', {{ $battle->arena_turn }})" wire:loading.attr="disabled" @disabled($state[$mySide]['mp'] < $action['mana'])>
                                            <strong>{{ $action['name'] }}</strong><span>{{ $action['description'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            <x-filament::button class="mt-4" wire:click="forfeit" wire:confirm="Forfeit this battle? In a rated PvP duel this counts as a loss." wire:loading.attr="disabled" color="gray" size="sm">Forfeit battle</x-filament::button>
                        @elseif ($battle->arena_status === 'completed')
                            <div class="arena-result" role="status">
                                <h3>{{ $battle->outcome === 'draw' ? 'An evenly matched battle — draw' : ($battle->winner_id === $player->id ? 'Victory! Well fought.' : 'Defeat — regroup and try a new strategy.') }}</h3>
                                @if ($state['timeout'] ?? false)<p>The player whose turn expired forfeited this battle.</p>@endif
                                @if ($battle->battle_type === 'pve')
                                    <p>Rewards: {{ $battle->experience_gained }} XP · {{ $battle->gold_gained }} gold · {{ count($battle->items_gained ?? []) }} item(s).</p>
                                    <p>NPC rewards can be claimed once per opponent every {{ config('arena.reward_cooldown_minutes') }} minutes. A full inventory prevents new item drops.</p>
                                @else
                                    <p>{{ $state['rated'] ? 'Rated duel' : 'Practice rematch — no rating changes' }} · Your rating change: {{ $battle->attacker_id === $player->id ? $battle->rating_change : -$battle->rating_change }}.</p>
                                    <p>PvP sparring preserves both players’ world health and mana. No gold or XP is awarded.</p>
                                @endif
                            </div>
                        @endif
                        <details class="mt-5" @if ($battle->arena_status === 'completed') open @endif>
                            <summary class="cursor-pointer font-semibold">Combat chronicle · {{ count($battle->battle_log ?? []) }} actions</summary>
                            <ol class="arena-log" aria-label="Battle action history">
                                @forelse ($battle->battle_log ?? [] as $entry)
                                    <li wire:key="log-{{ $battle->id }}-{{ $loop->index }}"><span class="arena-log-turn">{{ $entry['round'] }}</span><p><strong>{{ $entry['name'] }}</strong> used {{ config('arena.actions')[$entry['action']]['name'] }}.@if ($entry['damage']) Dealt {{ $entry['damage'] }} damage.@endif @if ($entry['healing']) Restored {{ $entry['healing'] }} health.@endif @if ($entry['critical']) <strong>Critical hit!</strong>@endif</p></li>
                                @empty
                                    <li>Choose your opening move to begin the chronicle.</li>
                                @endforelse
                            </ol>
                        </details>
                    @endif
                </x-filament::section>
            @endif

            <x-filament::section heading="The hunting board" :description="$npcs->total().' NPC encounters, from forgiving training sentinels to regional bosses. Reward amounts shown use the standard rate; game multipliers apply.'">
                <div class="adventure-paths">
                    @forelse ($npcs as $npc)
                        <article class="adventure-path" wire:key="npc-{{ $npc->id }}">
                            <div class="flex flex-wrap gap-2"><x-filament::badge :color="$npc->difficulty === 'Boss' ? 'danger' : 'info'">{{ $npc->difficulty }}</x-filament::badge><x-filament::badge color="gray">Level {{ $npc->level }}</x-filament::badge></div>
                            <h3>{{ $npc->name }}</h3><p>{{ $npc->description }}</p>
                            <p>{{ $npc->region }} · {{ ucfirst($npc->behavior) }} tactics<br>{{ $npc->stats['health'] }} health · {{ $npc->experience_reward }} XP · {{ $npc->gold_reward }} gold</p>
                            @if ($npc->lootItem)<x-filament::badge color="warning">Loot: {{ $npc->lootItem->name }}</x-filament::badge>@endif
                            <x-filament::button wire:click="startNpc({{ $npc->id }})" wire:loading.attr="disabled" :disabled="(bool) $current || $player->level < max(1, $npc->level - 3)" size="sm">{{ $player->level < max(1, $npc->level - 3) ? 'Unlocks at level '.max(1, $npc->level - 3) : 'Enter encounter' }}</x-filament::button>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">The hunting board is being prepared. Ask your administrator to install the starter adventure and NPCs.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <div>{{ $npcs->links() }}</div>

            <x-filament::section heading="Player duels" description="Consensual challenges for players within five levels. Rematches within ten minutes are practice duels. Challenges expire after fifteen minutes.">
                @if (! $pvpEnabled)
                    <p class="text-sm text-gray-600 dark:text-gray-400">The game administrator has disabled PvP. NPC encounters remain available.</p>
                @elseif (! $player->arena_opt_in)
                    <p class="text-sm text-gray-600 dark:text-gray-400">Opt in above to appear in matchmaking and challenge other players. You can leave matchmaking between battles.</p>
                @else
                    <label for="opponent-search" class="text-sm font-medium">Find an opponent</label>
                    <x-filament::input.wrapper class="mt-2 mb-4"><x-filament::input id="opponent-search" wire:model.live.debounce.300ms="opponentSearch" maxlength="80" placeholder="Search player name" /></x-filament::input.wrapper>
                    <div class="adventure-paths">
                        @forelse ($opponents as $opponent)
                            <article class="adventure-path" wire:key="opponent-{{ $opponent->id }}">
                                <h3>{{ $opponent->username }}</h3><p>Level {{ $opponent->level }} · Arena rating {{ $opponent->arena_rating }}</p>
                                <x-filament::button wire:click="challenge({{ $opponent->id }})" wire:loading.attr="disabled" :disabled="(bool) $current" size="sm">Send challenge</x-filament::button>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">No matching opted-in adventurers yet. Try an NPC encounter while you wait.</p>
                        @endforelse
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Prepare your loadout" description="Equip owned items between battles. Equipment bonuses and level growth are included when a battle starts." collapsible collapsed>
                <div class="adventure-paths">
                    @forelse ($inventory as $item)
                        <article class="adventure-path" wire:key="loadout-{{ $item->id }}">
                            <h3>{{ $item->name }}</h3><p>{{ ucfirst($item->slot) }} · Minimum level {{ $item->min_level }}</p>
                            <p>STR +{{ $item->strength_bonus }} · DEF +{{ $item->defense_bonus }} · AGI +{{ $item->agility_bonus }} · INT +{{ $item->intelligence_bonus }}<br>Health +{{ $item->health_bonus }} · Mana +{{ $item->mana_bonus }}</p>
                            <x-filament::button wire:click="equip({{ $item->id }})" wire:loading.attr="disabled" :disabled="(bool) $current || $player->level < $item->min_level || ($equipment->get($item->slot)?->item_id ?? null) === $item->id" size="sm" color="gray">{{ ($equipment->get($item->slot)?->item_id ?? null) === $item->id ? 'Equipped' : 'Equip item' }}</x-filament::button>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">Complete account setup to receive your starter kit, or earn equipment from quests.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section heading="Your battle reports" description="Private records of your most recent eight arena encounters." collapsible>
                <div class="arena-reports">
                    @forelse ($recent as $report)
                        <button class="arena-report" type="button" wire:key="report-{{ $report->id }}" wire:click="showBattle({{ $report->id }})"><strong>#{{ $report->id }} · {{ $report->opponent_name ?? ($report->attacker_id === $player->id ? $report->defender?->username : $report->attacker?->username) }}</strong><span>{{ ucfirst($report->arena_status) }} · {{ $report->created_at->diffForHumans() }}</span></button>
                    @empty
                        <p class="text-sm text-gray-500">Your first encounter will appear here.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    @endunless
</x-filament-panels::page>
