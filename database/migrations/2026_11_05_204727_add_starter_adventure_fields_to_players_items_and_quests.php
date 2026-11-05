<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->string('character_class')->nullable();
            $table->timestamp('starter_kit_granted_at')->nullable();
        });
        foreach (['items', 'quests'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('starter_key')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (['items', 'quests'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropUnique(['starter_key']);
                $table->dropColumn('starter_key');
            });
        }
        Schema::table('players', function (Blueprint $table): void {
            $table->dropColumn(['character_class', 'starter_kit_granted_at']);
        });
    }
};
