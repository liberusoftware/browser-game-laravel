<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('username')->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('region')->nullable()->index();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->boolean('age_verified')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'username']);
        });

        Schema::create('browser_game_account_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('account_id')->constrained('browser_game_accounts')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'revoked_at']);
        });

        Schema::create('browser_game_account_bans', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('account_id')->constrained('browser_game_accounts')->cascadeOnDelete();
            $table->string('reason');
            $table->string('scope')->default('account');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('issued_by')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'revoked_at', 'ends_at']);
        });

        Schema::create('browser_game_account_recoveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('account_id')->constrained('browser_game_accounts')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_account_privacy', function (Blueprint $table): void {
            $table->foreignUuid('account_id')->primary()->constrained('browser_game_accounts')->cascadeOnDelete();
            $table->string('profile_visibility')->default('private');
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('analytics_consent')->default(false);
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deletion_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_account_privacy');
        Schema::dropIfExists('browser_game_account_recoveries');
        Schema::dropIfExists('browser_game_account_bans');
        Schema::dropIfExists('browser_game_account_sessions');
        Schema::dropIfExists('browser_game_accounts');
    }
};
