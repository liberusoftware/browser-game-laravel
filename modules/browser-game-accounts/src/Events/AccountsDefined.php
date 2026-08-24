<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class AccountsDefined
{
    use Dispatchable;

    public function __construct(public string $recordId) {}
}
