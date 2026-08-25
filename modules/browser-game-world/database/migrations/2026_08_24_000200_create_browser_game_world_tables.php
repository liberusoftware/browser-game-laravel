<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_world_entities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('team_id')->nullable()->index();
            $table->uuid('world_id')->nullable()->index();
            $table->string('kind', 32)->index();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active')->index();
            $table->json('attributes')->nullable();
            $table->json('coordinates')->nullable();
            $table->string('unlock_key')->nullable()->index();
            $table->timestamps();
            $table->unique(['world_id', 'kind', 'slug']);
        });

        Schema::create('browser_game_world_travels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('team_id')->nullable()->index();
            $table->uuid('actor_id')->index();
            $table->uuid('origin_id')->nullable();
            $table->uuid('destination_id');
            $table->string('idempotency_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->unique(['actor_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_world_travels');
        Schema::dropIfExists('browser_game_world_entities');
    }
};
