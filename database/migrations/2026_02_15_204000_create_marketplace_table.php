<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('price_per_unit');
            $table->string('status')->default('active'); // active, sold, cancelled
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('buyer_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
