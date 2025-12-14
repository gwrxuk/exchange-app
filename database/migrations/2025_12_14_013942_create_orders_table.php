<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 20, 8);
            $table->decimal('amount', 20, 8);
            $table->decimal('remaining_amount', 20, 8);
            $table->tinyInteger('status')->default(1); // 1: Open, 2: Filled, 3: Cancelled
            $table->timestamps();
            
            $table->index(['symbol', 'status', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
