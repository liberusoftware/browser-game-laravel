<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('slot')->nullable(); // weapon, armor, helmet, boots, gloves, accessory
            $table->integer('strength_bonus')->default(0);
            $table->integer('defense_bonus')->default(0);
            $table->integer('agility_bonus')->default(0);
            $table->integer('intelligence_bonus')->default(0);
            $table->integer('health_bonus')->default(0);
            $table->integer('mana_bonus')->default(0);
            $table->integer('min_level')->default(1);
            $table->integer('sell_price')->default(0);
            $table->integer('buy_price')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'slot', 'strength_bonus', 'defense_bonus', 'agility_bonus',
                'intelligence_bonus', 'health_bonus', 'mana_bonus',
                'min_level', 'sell_price', 'buy_price',
            ]);
        });
    }
};
