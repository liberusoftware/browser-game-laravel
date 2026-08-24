<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_collection_progress', function (Blueprint $table): void {
            $table->unsignedInteger('completion_count')->default(0)->after('quantity');
            $table->timestamp('reward_claimed_at')->nullable()->after('completed_at');
            $table->string('last_operation_key')->nullable()->after('reward_claimed_at');
            $table->index(['actor_id', 'last_operation_key']);
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_collection_progress', function (Blueprint $table): void {
            $table->dropIndex(['actor_id', 'last_operation_key']);
            $table->dropColumn(['completion_count', 'reward_claimed_at', 'last_operation_key']);
        });
    }
};
