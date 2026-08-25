<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_live_ops', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('kind')->default('event')->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('browser_game_live_ops_claims', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->foreignUuid('live_ops_id')->constrained('browser_game_live_ops')->cascadeOnDelete();
            $table->string('claim_key');
            $table->string('status')->default('claimed');
            $table->json('grant')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'live_ops_id', 'claim_key']);
        });
        Schema::create('browser_game_live_ops_rollbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('live_ops_id')->constrained('browser_game_live_ops')->cascadeOnDelete();
            $table->unsignedInteger('from_version');
            $table->unsignedInteger('to_version');
            $table->string('actor_id')->index();
            $table->text('reason');
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_live_ops_rollbacks');
        Schema::dropIfExists('browser_game_live_ops_claims');
        Schema::dropIfExists('browser_game_live_ops');
    }
};
