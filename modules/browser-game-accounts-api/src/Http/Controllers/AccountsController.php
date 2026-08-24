<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;
use Liberu\BrowserGame\Accounts\Queries\AccountsQuery;

final class AccountsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(AccountsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, AccountsRecord $account): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $account = app(AccountsQuery::class)->visible(null, (string) $teamId)
            ->whereKey($account->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($account)]);
    }

    private function resource(Model $account): array
    {
        return ['id' => (string) $account->getKey(), 'type' => 'browser-game-account', 'attributes' => ['name' => $account->getAttribute('name'), 'status' => $account->getAttribute('status'), 'data' => $account->getAttribute('data'), 'tenant_id' => $account->getAttribute('tenant_id'), 'team_id' => $account->getAttribute('team_id')]];
    }
}
