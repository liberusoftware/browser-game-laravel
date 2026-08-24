<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource;

final class CreateCompetition extends CreateRecord
{
    protected static string $resource = CompetitionResource::class;
}
