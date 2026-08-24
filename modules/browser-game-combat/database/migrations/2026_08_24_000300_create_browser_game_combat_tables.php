<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_combats', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('actor_id')->index();
            $table->string('opponent_id')->index();
            $table->string('status')->index();
            $table->unsignedInteger('turn')->default(1);
            $table->string('seed', 128);
            $table->string('ruleset_version', 32)->default('1');
            $table->json('state');
            $table->timestamps();
            $table->unique(['actor_id', 'idempotency_key']);
            $table->string('idempotency_key')->nullable();
        });
        Schema::create('browser_game_combat_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('combat_id')->constrained('browser_game_combats')->cascadeOnDelete();
            $table->unsignedInteger('turn');
            $table->string('actor_id');
            $table->string('action');
            $table->integer('value')->default(0);
            $table->json('effects')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();
            $table->unique(['combat_id', 'idempotency_key']);
            $table->index(['combat_id', 'turn']);
        });
        Schema::create('browser_game_combat_catalog', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('kind')->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('cooldown')->default(0);
            $table->json('effects')->nullable();
            $table->json('data')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_combat_catalog');
        Schema::dropIfExists('browser_game_combat_actions');
        Schema::dropIfExists('browser_game_combats');
    }
};
