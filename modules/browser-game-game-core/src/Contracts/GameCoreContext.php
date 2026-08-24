<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Contracts;

interface GameCoreContext
{
    public function actorId(): ?string;

    public function tenantId(): ?string;

    public function teamId(): ?string;
}
