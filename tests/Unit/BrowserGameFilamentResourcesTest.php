<?php

it('resolves every browser game Filament resource page definition', function (string $resource): void {
    $pages = $resource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);

    foreach ($pages as $page) {
        expect($page->getPage())->toBeString()->and(class_exists($page->getPage()))->toBeTrue();
    }
})->with([
    'accounts' => 'Liberu\\BrowserGame\\AccountsFilament\\Resources\\AccountsResource',
    'characters' => 'Liberu\\BrowserGame\\CharactersFilament\\Resources\\CharacterResource',
    'collections' => 'Liberu\\BrowserGame\\CollectionsFilament\\Resources\\CollectionsResource',
    'combat battles' => 'Liberu\\BrowserGame\\CombatFilament\\Resources\\CombatBattleResource',
    'combat definitions' => 'Liberu\\BrowserGame\\CombatFilament\\Resources\\CombatDefinitionResource',
    'commerce' => 'Liberu\\BrowserGame\\CommerceFilament\\Resources\\CommerceResource',
    'competition' => 'Liberu\\BrowserGame\\CompetitionFilament\\Resources\\CompetitionResource',
    'crafting' => 'Liberu\\BrowserGame\\CraftingFilament\\Resources\\CraftingResource',
    'economy' => 'Liberu\\BrowserGame\\EconomyFilament\\Resources\\EconomyResource',
    'feature flags' => 'Liberu\\BrowserGame\\GameCoreFilament\\Resources\\GameFeatureFlagResource',
    'worlds' => 'Liberu\\BrowserGame\\GameCoreFilament\\Resources\\GameWorldResource',
    'items' => 'Liberu\\BrowserGame\\ItemsFilament\\Resources\\ItemsResource',
    'live ops' => 'Liberu\\BrowserGame\\LiveOpsFilament\\Resources\\LiveOpsResource',
    'moderation and analytics' => 'Liberu\\BrowserGame\\ModerationAndAnalyticsFilament\\Resources\\ModerationAndAnalyticsResource',
    'quests' => 'Liberu\\BrowserGame\\QuestsFilament\\Resources\\QuestResource',
    'social' => 'Liberu\\BrowserGame\\SocialFilament\\Resources\\SocialResource',
    'world entities' => 'Liberu\\BrowserGame\\WorldFilament\\Resources\\WorldEntityResource',
]);
