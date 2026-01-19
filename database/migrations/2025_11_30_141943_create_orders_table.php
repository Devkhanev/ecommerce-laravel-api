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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_price', 10, 2)->unsigned();
            $table->enum('status', ['pending', 'pending_payment', 'paid', 'canceled', 'shipped', 'delivered'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Индексы
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
