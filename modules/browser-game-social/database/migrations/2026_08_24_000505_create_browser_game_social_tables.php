<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_social', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('kind')->default('guild')->index();
            $table->string('status')->default('active')->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('target_id')->nullable()->index();
            $table->text('body')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('browser_game_social_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('social_id')->constrained('browser_game_social')->cascadeOnDelete();
            $table->string('actor_id')->index();
            $table->string('role')->default('member');
            $table->string('status')->default('active');
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->unique(['social_id', 'actor_id']);
        });
        Schema::create('browser_game_social_activity', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->string('kind');
            $table->string('target_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_social_activity');
        Schema::dropIfExists('browser_game_social_memberships');
        Schema::dropIfExists('browser_game_social');
    }
};
