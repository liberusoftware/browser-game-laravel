<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('player_friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('players')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, accepted, blocked
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_friends');
    }
};
