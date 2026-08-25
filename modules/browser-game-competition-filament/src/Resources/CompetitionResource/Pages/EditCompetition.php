<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource;

final class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;
}
