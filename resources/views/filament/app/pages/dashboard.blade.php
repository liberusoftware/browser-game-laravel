<x-filament-panels::page>
    @php
        $player = auth()->user()->player;
        $level = max(1, (int) ($player?->level ?? 1));
        $experience = (int) ($player?->experience ?? 0);
        $progress = max(0, min(100, $experience - (($level - 1) * 100)));
    @endphp
    <section class="adventure-hero" aria-labelledby="adventure-heading">
        <div class="adventure-hero-copy">
            <p class="adventure-eyebrow">Your next chapter</p>
            <h2 id="adventure-heading">{{ $player ? 'Welcome back, '.$player->username : 'Every legend starts somewhere.' }}</h2>
            <p>{{ $player ? 'Find your next quest, earn new equipment, and make every adventure count.' : 'Get to know your world and take the first step toward your own adventure.' }}</p>
            <div class="adventure-actions">
                <x-filament::button tag="a" :href="\App\Filament\App\Pages\QuestJournal::getUrl(panel: 'app')" icon="heroicon-o-map">Explore quests</x-filament::button>
                <x-filament::button tag="a" :href="\App\Filament\App\Pages\AdventureGuide::getUrl(panel: 'app')" color="gray" icon="heroicon-o-book-open">{{ auth()->user()->game_guide_completed_at ? 'Revisit the guide' : 'Start the beginner guide' }}</x-filament::button>
            </div>
        </div>
        <div class="adventure-level">
            <x-filament::icon icon="heroicon-o-shield-check" class="h-10 w-10" />
            <strong>{{ $player ? 'Level '.$level : 'New adventurer' }}</strong>
            <span>{{ $player ? number_format($experience).' XP earned' : 'Your story is waiting' }}</span>
            @if ($player)
                @if ($player->character_class)<span>{{ config('starter-adventure.classes')[$player->character_class]['name'] ?? ucfirst($player->character_class) }}</span>@endif
                <progress max="100" value="{{ $progress }}" aria-label="Progress toward level {{ $level + 1 }}">{{ $progress }}%</progress>
                <span>{{ max(0, ($level * 100) - $experience) }} XP to level {{ $level + 1 }}</span>
            @endif
        </div>
    </section>
    @unless (auth()->user()->hasCompletedOnboarding())
        <x-filament::section heading="Make yourself at home" description="Set your display name and team before you begin.">
            <x-filament::button tag="a" :href="\App\Filament\App\Pages\AccountSetup::getUrl(panel: 'app')" size="sm">Finish account setup</x-filament::button>
        </x-filament::section>
    @endunless
    <x-filament::section heading="Ready to test your strength?" description="Face regional NPCs, equip your rewards, or invite another player to a tactical duel.">
        <x-filament::button tag="a" :href="\App\Filament\App\Pages\BattleArena::getUrl(panel: 'app')" icon="heroicon-o-bolt">Enter the battle arena</x-filament::button>
    </x-filament::section>
    <div class="adventure-paths">
        <a class="adventure-path" href="{{ \App\Filament\App\Pages\QuestJournal::getUrl(panel: 'app') }}">
            <x-filament::icon icon="heroicon-o-map" class="h-7 w-7 text-primary-500" />
            <h3>Find your next quest</h3><p>Discover objectives and preview the rewards before committing.</p><span>Open quest journal &rarr;</span>
        </a>
        <a class="adventure-path" href="#inventory">
            <x-filament::icon icon="heroicon-o-archive-box" class="h-7 w-7 text-primary-500" />
            <h3>Know your equipment</h3><p>See what you have collected and plan your next upgrade.</p><span>Check inventory &darr;</span>
        </a>
        <a class="adventure-path" href="{{ \App\Filament\App\Pages\AdventureGuide::getUrl(panel: 'app') }}">
            <x-filament::icon icon="heroicon-o-sparkles" class="h-7 w-7 text-primary-500" />
            <h3>Build your confidence</h3><p>A short guide to quests, experience, and your first rewards.</p><span>Read the guide &rarr;</span>
        </a>
    </div>
    <div id="inventory" class="scroll-mt-6">{{ $this->content }}</div>
</x-filament-panels::page>
