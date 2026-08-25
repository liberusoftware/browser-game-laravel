<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Crafting\Events\CraftingCancelled;
use Liberu\BrowserGame\Crafting\Events\CraftingCompleted;
use Liberu\BrowserGame\Crafting\Events\CraftingDefined;
use Liberu\BrowserGame\Crafting\Events\CraftingDiscovered;
use Liberu\BrowserGame\Crafting\Events\CraftingFailed;
use Liberu\BrowserGame\Crafting\Events\CraftingQueued;
use Liberu\BrowserGame\Crafting\Events\CraftingSalvaged;
use Liberu\BrowserGame\Crafting\Models\CraftingDiscovery;
use Liberu\BrowserGame\Crafting\Models\CraftingOutput;
use Liberu\BrowserGame\Crafting\Models\CraftingProfession;
use Liberu\BrowserGame\Crafting\Models\CraftingQueue;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;
use Liberu\BrowserGame\Crafting\Models\CraftingResource;

final class CraftingManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CraftingRecord
    {
        $this->required($name, 'name');
        $record = DB::transaction(fn (): CraftingRecord => CraftingRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $data['slug'] ?? Str::slug($name),
            'description' => $data['description'] ?? null,
            'profession' => $data['profession'] ?? null,
            'min_level' => max(1, (int) ($data['min_level'] ?? 1)),
            'success_rate' => $this->percentage($data['success_rate'] ?? 100),
            'crafting_time_seconds' => max(0, (int) ($data['crafting_time_seconds'] ?? 0)),
            'materials' => $this->materials($data['materials'] ?? []),
            'outputs' => $data['outputs'] ?? [],
            'salvage' => $data['salvage'] ?? [],
            'discovery_requirements' => $data['discovery_requirements'] ?? [],
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CraftingDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function grantResource(string $actorId, string $resourceKey, int $quantity): CraftingResource
    {
        $this->required($actorId, 'actor_id');
        $this->required($resourceKey, 'resource_key');
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        }

        return DB::transaction(function () use ($actorId, $resourceKey, $quantity): CraftingResource {
            $resource = CraftingResource::query()->lockForUpdate()->firstOrCreate(
                ['actor_id' => $actorId, 'resource_key' => $resourceKey],
                ['quantity' => 0],
            );
            $resource->increment('quantity', $quantity);

            return $resource->refresh();
        });
    }

    public function setProfession(string $actorId, string $profession, int $level = 1, int $experience = 0): CraftingProfession
    {
        $this->required($actorId, 'actor_id');
        $this->required($profession, 'profession');
        if ($level < 1 || $experience < 0) {
            throw ValidationException::withMessages(['profession' => 'Profession level and experience are invalid.']);
        }

        return CraftingProfession::query()->updateOrCreate(
            ['actor_id' => $actorId, 'profession' => $profession],
            ['level' => $level, 'experience' => $experience],
        );
    }

    public function gainProfessionExperience(string $actorId, string $profession, int $amount): CraftingProfession
    {
        if ($amount < 0) {
            throw ValidationException::withMessages(['experience' => 'Experience cannot be negative.']);
        }

        return DB::transaction(function () use ($actorId, $profession, $amount): CraftingProfession {
            $current = CraftingProfession::query()->where('actor_id', $actorId)->where('profession', $profession)->lockForUpdate()->first();
            if ($current === null) {
                $current = CraftingProfession::query()->create(['actor_id' => $actorId, 'profession' => $profession, 'level' => 1, 'experience' => 0]);
            }
            $experience = (int) $current->experience + $amount;
            $current->update(['experience' => $experience, 'level' => max(1, intdiv($experience, 100) + 1)]);

            return $current->refresh();
        });
    }

    public function discover(string $actorId, CraftingRecord $recipe, ?string $tenantId = null, ?string $teamId = null): CraftingDiscovery
    {
        $this->required($actorId, 'actor_id');
        $this->assertRecipeScope($recipe, $tenantId, $teamId);
        $created = false;
        $discovery = DB::transaction(function () use ($actorId, $recipe, &$created): CraftingDiscovery {
            $discovery = CraftingDiscovery::query()->where('actor_id', $actorId)->where('recipe_id', $recipe->getKey())->lockForUpdate()->first();
            if ($discovery !== null) {
                return $discovery;
            }
            $created = true;

            return CraftingDiscovery::query()->create(['actor_id' => $actorId, 'recipe_id' => $recipe->getKey(), 'discovered_at' => now()]);
        });
        if ($created) {
            CraftingDiscovered::dispatch((string) $recipe->getKey(), $actorId);
        }

        return $discovery;
    }

    public function queueCraft(string $actorId, CraftingRecord $recipe, int $quantity = 1, int $quality = 100, ?string $idempotencyKey = null, int $actorLevel = 1, ?string $tenantId = null, ?string $teamId = null): CraftingQueue
    {
        $this->assertRecipeScope($recipe, $tenantId, $teamId);
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be between 1 and 1000.']);
        }
        if ($recipe->status !== 'active' || $actorLevel < (int) $recipe->min_level) {
            throw ValidationException::withMessages(['recipe' => 'The recipe is unavailable at the current level.']);
        }
        $requirements = (array) $recipe->discovery_requirements;
        if (($requirements['required'] ?? false) && ! CraftingDiscovery::query()->where('actor_id', $actorId)->where('recipe_id', $recipe->getKey())->exists()) {
            throw ValidationException::withMessages(['recipe' => 'The recipe has not been discovered.']);
        }

        return DB::transaction(function () use ($actorId, $recipe, $quantity, $quality, $idempotencyKey): CraftingQueue {
            if ($idempotencyKey !== null) {
                $existing = CraftingQueue::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing !== null) {
                    return $existing->load('recipe');
                }
            }
            $materials = (array) $recipe->materials;
            foreach ($materials as $key => $required) {
                $this->consumeResource($actorId, (string) $key, (int) $required * $quantity);
            }
            $now = now();
            $queue = CraftingQueue::query()->create([
                'id' => (string) Str::uuid(),
                'actor_id' => $actorId,
                'recipe_id' => $recipe->getKey(),
                'quantity' => $quantity,
                'status' => 'queued',
                'quality' => $this->percentage($quality),
                'idempotency_key' => $idempotencyKey,
                'started_at' => $now,
                'completes_at' => $now->addSeconds((int) $recipe->crafting_time_seconds),
                'metadata' => ['materials' => $materials, 'success' => random_int(1, 10000) <= ((float) $recipe->success_rate * 100)],
            ]);
            CraftingQueued::dispatch((string) $queue->getKey(), $actorId, (string) $recipe->getKey());

            return $queue->load('recipe');
        });
    }

    public function complete(CraftingQueue $queue): CraftingQueue
    {
        $outcome = 'unchanged';
        $updated = DB::transaction(function () use ($queue, &$outcome): CraftingQueue {
            $queue = CraftingQueue::query()->lockForUpdate()->with('recipe')->findOrFail($queue->getKey());
            if ($queue->status !== 'queued') {
                return $queue;
            }
            if ($queue->completes_at !== null && $queue->completes_at->isFuture()) {
                throw ValidationException::withMessages(['queue' => 'The crafting queue has not completed yet.']);
            }
            if (! (bool) (($queue->metadata ?? [])['success'] ?? false)) {
                $queue->update(['status' => 'failed', 'completed_at' => now(), 'failure_reason' => 'Crafting failed.']);
                $outcome = 'failed';

                return $queue->refresh();
            }
            foreach ((array) $queue->recipe->outputs as $key => $output) {
                [$outputKey, $amount] = is_array($output)
                    ? [(string) ($output['key'] ?? $output['item'] ?? $key), (int) ($output['quantity'] ?? 1)]
                    : [(string) $key, (int) $output];
                CraftingOutput::query()->create([
                    'actor_id' => $queue->actor_id,
                    'queue_id' => $queue->getKey(),
                    'recipe_id' => $queue->recipe_id,
                    'output_key' => $outputKey,
                    'quantity' => max(1, $amount * $queue->quantity),
                    'quality' => $queue->quality,
                    'provenance' => ['recipe_id' => $queue->recipe_id, 'queue_id' => $queue->getKey()],
                ]);
            }
            $queue->update(['status' => 'completed', 'completed_at' => now()]);
            $this->gainProfessionExperienceLocked($queue->actor_id, (string) ($queue->recipe->profession ?? 'general'), $queue->quantity);
            $outcome = 'completed';

            return $queue->refresh();
        });
        if ($outcome === 'completed') {
            CraftingCompleted::dispatch((string) $updated->getKey(), $updated->actor_id, $updated->quality);
        } elseif ($outcome === 'failed') {
            CraftingFailed::dispatch((string) $updated->getKey(), $updated->actor_id, (string) $updated->failure_reason);
        }

        return $updated->load(['recipe', 'outputs']);
    }

    public function cancel(CraftingQueue $queue): CraftingQueue
    {
        $cancelled = false;
        $updated = DB::transaction(function () use ($queue, &$cancelled): CraftingQueue {
            $queue = CraftingQueue::query()->lockForUpdate()->findOrFail($queue->getKey());
            if ($queue->status !== 'queued') {
                return $queue;
            }
            foreach ((array) (($queue->metadata ?? [])['materials'] ?? []) as $key => $amount) {
                $this->grantResource($queue->actor_id, (string) $key, (int) $amount * $queue->quantity);
            }
            $queue->update(['status' => 'cancelled', 'completed_at' => now()]);
            $cancelled = true;

            return $queue->refresh();
        });
        if ($cancelled) {
            CraftingCancelled::dispatch((string) $updated->getKey(), $updated->actor_id);
        }

        return $updated;
    }

    public function salvage(CraftingQueue $queue): CraftingQueue
    {
        $salvaged = false;
        $updated = DB::transaction(function () use ($queue, &$salvaged): CraftingQueue {
            $queue = CraftingQueue::query()->lockForUpdate()->with('recipe')->findOrFail($queue->getKey());
            if (! in_array($queue->status, ['completed', 'failed'], true)) {
                if ($queue->status === 'salvaged') {
                    return $queue;
                }
                throw ValidationException::withMessages(['queue' => 'Only completed or failed crafting can be salvaged.']);
            }
            foreach ((array) $queue->recipe->salvage as $key => $amount) {
                $this->grantResource($queue->actor_id, (string) $key, (int) $amount * $queue->quantity);
            }
            $queue->update(['status' => 'salvaged']);
            $salvaged = true;

            return $queue->refresh();
        });
        if ($salvaged) {
            CraftingSalvaged::dispatch((string) $updated->getKey(), $updated->actor_id);
        }

        return $updated;
    }

    private function gainProfessionExperienceLocked(string $actorId, string $profession, int $amount): void
    {
        $current = CraftingProfession::query()->where('actor_id', $actorId)->where('profession', $profession)->lockForUpdate()->first();
        if ($current === null) {
            $current = CraftingProfession::query()->create(['actor_id' => $actorId, 'profession' => $profession, 'level' => 1, 'experience' => 0]);
        }
        $experience = (int) $current->experience + $amount;
        $current->update(['experience' => $experience, 'level' => max(1, intdiv($experience, 100) + 1)]);
    }

    private function consumeResource(string $actorId, string $resourceKey, int $quantity): void
    {
        $resource = CraftingResource::query()->where('actor_id', $actorId)->where('resource_key', $resourceKey)->lockForUpdate()->first();
        if ($resource === null || $resource->quantity < $quantity) {
            throw ValidationException::withMessages(['resources' => 'Insufficient crafting resources.']);
        }
        $resource->quantity === $quantity ? $resource->delete() : $resource->decrement('quantity', $quantity);
    }

    private function materials(array $materials): array
    {
        foreach ($materials as $key => $quantity) {
            if ((int) $quantity < 1) {
                throw ValidationException::withMessages(['materials' => 'Material quantities must be positive.']);
            }
        }

        return array_map('intval', $materials);
    }

    private function assertRecipeScope(CraftingRecord $recipe, ?string $tenantId, ?string $teamId): void
    {
        if (($recipe->tenant_id !== null && $recipe->tenant_id !== $tenantId) || ($recipe->team_id !== null && (string) $recipe->team_id !== (string) $teamId)) {
            throw ValidationException::withMessages(['recipe' => 'The recipe is not available in the current context.']);
        }
    }

    private function percentage(mixed $value): int|float
    {
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
            throw ValidationException::withMessages(['quality' => 'The value must be between 0 and 100.']);
        }

        return $value;
    }

    private function required(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([$field => 'A value is required.']);
        }
    }
}
