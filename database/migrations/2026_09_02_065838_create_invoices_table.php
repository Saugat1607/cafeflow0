<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Invoice information
            $table->string('invoice_number')->unique();
            $table->dateTime('invoice_date');

            // Link to existing order
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            // Cafe table number/name if you want to store it
            // No foreign key because your database has no "tables" table.
            $table->string('table_number')->nullable();

            // Customer
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();

            // Financial information
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Payment
            $table->enum('payment_method', [
                'cash',
                'card',
                'online'
            ])->default('cash');

            $table->enum('payment_status', [
                'paid',
                'pending',
                'cancelled'
            ])->default('paid');

            // Additional information
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
