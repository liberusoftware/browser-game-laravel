<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource;

final class CreateCrafting extends CreateRecord
{
    protected static string $resource = CraftingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        abort_unless($team !== null, 403);

        return array_merge($data, ['tenant_id' => $team?->getAttribute('tenant_id'), 'team_id' => $team?->getKey()]);
    }
}
