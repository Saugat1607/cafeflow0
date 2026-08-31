<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->enum('type', [
                'in',
                'out',
                'adjustment'
            ]);

            $table->decimal('quantity', 12, 3);

            $table->decimal('unit_cost', 12, 2)->default(0);

            $table->decimal('total_cost', 12, 2)->default(0);

            $table->string('reason')->nullable();

            $table->string('reference')->nullable();

            $table->date('transaction_date');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('type');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
