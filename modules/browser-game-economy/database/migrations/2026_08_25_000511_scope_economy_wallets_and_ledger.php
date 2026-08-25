<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_economy_wallets', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->dropUnique(['actor_id', 'currency_code']);
            $table->unique(['actor_id', 'currency_code', 'tenant_id', 'team_id']);
        });

        Schema::table('browser_game_economy_ledger', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_economy_ledger', function (Blueprint $table): void {
            $table->dropIndex('browser_game_economy_ledger_tenant_id_index');
            $table->dropIndex('browser_game_economy_ledger_team_id_index');
            $table->dropColumn(['tenant_id', 'team_id']);
        });

        Schema::table('browser_game_economy_wallets', function (Blueprint $table): void {
            $table->dropUnique(['actor_id', 'currency_code', 'tenant_id', 'team_id']);
            $table->dropIndex('browser_game_economy_wallets_tenant_id_index');
            $table->dropIndex('browser_game_economy_wallets_team_id_index');
            $table->dropColumn(['tenant_id', 'team_id']);
            $table->unique(['actor_id', 'currency_code']);
        });
    }
};
