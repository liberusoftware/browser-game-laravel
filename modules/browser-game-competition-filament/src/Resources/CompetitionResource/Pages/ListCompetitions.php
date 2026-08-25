<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource;

final class ListCompetitions extends ListRecords
{
    protected static string $resource = CompetitionResource::class;
}
