<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('result_item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('result_quantity')->default(1);
            $table->integer('min_level')->default(1);
            $table->integer('success_rate')->default(100); // percentage
            $table->integer('crafting_time_seconds')->default(0);
            $table->timestamps();
        });

        Schema::create('recipe_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('player_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamp('learned_at');
            $table->timestamps();

            $table->unique(['player_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_recipes');
        Schema::dropIfExists('recipe_materials');
        Schema::dropIfExists('recipes');
    }
};
