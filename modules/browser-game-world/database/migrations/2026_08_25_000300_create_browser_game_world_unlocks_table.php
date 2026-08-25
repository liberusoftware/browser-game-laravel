<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_world_unlocks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('team_id')->nullable()->index();
            $table->uuid('actor_id')->index();
            $table->uuid('entity_id')->nullable()->index();
            $table->string('unlock_key');
            $table->string('status', 24)->default('granted')->index();
            $table->json('metadata')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'unlock_key', 'team_id', 'status'], 'browser_game_world_unlocks_actor_key_scope_status');
            $table->unique(['actor_id', 'idempotency_key'], 'browser_game_world_unlocks_actor_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_world_unlocks');
    }
};
