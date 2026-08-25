<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_crafting', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('profession')->nullable()->index();
            $table->unsignedInteger('min_level')->default(1);
            $table->decimal('success_rate', 5, 2)->default(100);
            $table->unsignedInteger('crafting_time_seconds')->default(0);
            $table->json('materials')->nullable();
            $table->json('outputs')->nullable();
            $table->json('salvage')->nullable();
            $table->json('discovery_requirements')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_crafting_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->string('resource_key');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['actor_id', 'resource_key']);
        });

        Schema::create('browser_game_crafting_professions', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->string('profession');
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('experience')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'profession']);
        });

        Schema::create('browser_game_crafting_queues', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_id')->index();
            $table->foreignUuid('recipe_id')->constrained('browser_game_crafting')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('queued')->index();
            $table->unsignedTinyInteger('quality')->default(100);
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completes_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_crafting_discoveries', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->foreignUuid('recipe_id')->constrained('browser_game_crafting')->cascadeOnDelete();
            $table->timestamp('discovered_at');
            $table->timestamps();
            $table->unique(['actor_id', 'recipe_id']);
        });

        Schema::create('browser_game_crafting_outputs', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->foreignUuid('queue_id')->constrained('browser_game_crafting_queues')->cascadeOnDelete();
            $table->foreignUuid('recipe_id')->constrained('browser_game_crafting')->cascadeOnDelete();
            $table->string('output_key');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedTinyInteger('quality')->default(100);
            $table->json('provenance')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_crafting_outputs');
        Schema::dropIfExists('browser_game_crafting_discoveries');
        Schema::dropIfExists('browser_game_crafting_queues');
        Schema::dropIfExists('browser_game_crafting_professions');
        Schema::dropIfExists('browser_game_crafting_resources');
        Schema::dropIfExists('browser_game_crafting');
    }
};
