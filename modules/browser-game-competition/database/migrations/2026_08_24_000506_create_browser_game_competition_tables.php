<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_competition', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('kind')->default('pvp')->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('season')->default(1);
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('browser_game_competition_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('competition_id')->constrained('browser_game_competition')->cascadeOnDelete();
            $table->string('actor_id')->index();
            $table->string('status')->default('queued')->index();
            $table->integer('rating')->default(1000);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'actor_id']);
        });
        Schema::create('browser_game_competition_matches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('browser_game_competition')->cascadeOnDelete();
            $table->string('player_a');
            $table->string('player_b');
            $table->string('status')->default('active')->index();
            $table->string('winner_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('evidence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_competition_matches');
        Schema::dropIfExists('browser_game_competition_entries');
        Schema::dropIfExists('browser_game_competition');
    }
};
