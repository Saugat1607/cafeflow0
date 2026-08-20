<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained('bills')
                ->cascadeOnDelete();

            $table->foreignId('menu_item_id')
                ->nullable()
                ->constrained('menu_items')
                ->nullOnDelete();

            // Snapshot of menu item name at billing time
            $table->string('item_name');

            $table->integer('quantity');

            $table->decimal('unit_price', 12, 2);

            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
