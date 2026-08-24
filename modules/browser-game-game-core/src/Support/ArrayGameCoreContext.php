<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore\Support;

use Liberu\BrowserGame\GameCore\Contracts\GameCoreContext;

final readonly class ArrayGameCoreContext implements GameCoreContext
{
    public function __construct(
        private ?string $actor = null,
        private ?string $tenant = null,
        private ?string $team = null,
    ) {}

    public function actorId(): ?string
    {
        return $this->actor;
    }

    public function tenantId(): ?string
    {
        return $this->tenant;
    }

    public function teamId(): ?string
    {
        return $this->team;
    }
}
