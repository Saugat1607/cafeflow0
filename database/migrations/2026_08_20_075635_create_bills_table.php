<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();

            $table->string('bill_number')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('restaurant_table_id')
                ->nullable()
                ->constrained('restaurant_tables')
                ->nullOnDelete();

            $table->date('bill_date');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');

            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('bill_date');
            $table->index('restaurant_table_id');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
