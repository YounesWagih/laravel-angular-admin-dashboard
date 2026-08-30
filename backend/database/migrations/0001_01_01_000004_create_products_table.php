<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2)->unsigned();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
