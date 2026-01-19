<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->unsigned();
            $table->enum('status', ['pending', 'succeeded', 'canceled', 'waiting_for_capture']);
            $table->string('payment_method')->default('yookassa');
            $table->string('yookassa_payment_id')->unique()->nullable();
            $table->string('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index('order_id');
            $table->index('yookassa_payment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
