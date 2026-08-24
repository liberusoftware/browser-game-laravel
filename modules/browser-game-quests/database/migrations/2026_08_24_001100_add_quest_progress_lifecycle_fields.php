<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_quest_progress', function (Blueprint $table): void {
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('accepted_at');
            $table->timestamp('reward_claimed_at')->nullable()->after('completed_at');
            $table->unsignedInteger('completion_count')->default(0)->after('reward_claimed_at');
            $table->string('last_operation_key')->nullable()->after('completion_count');
            $table->index(['actor_id', 'last_operation_key']);
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_quest_progress', function (Blueprint $table): void {
            $table->dropIndex(['actor_id', 'last_operation_key']);
            $table->dropColumn(['accepted_at', 'completed_at', 'reward_claimed_at', 'completion_count', 'last_operation_key']);
        });
    }
};
