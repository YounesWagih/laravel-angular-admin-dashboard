<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_inventory_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type', 32);
            $table->bigInteger('on_hand_delta')->default(0);
            $table->bigInteger('reserved_delta')->default(0);
            $table->uuid('correlation_id')->nullable()->index();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['warehouse_inventory_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
