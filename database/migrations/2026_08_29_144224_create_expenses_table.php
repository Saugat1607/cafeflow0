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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            //expenses information
            $table->string('title');
            $table->text('description');
            $table->decimal('amount', 12,2);

            $table->string('category')->default('other');

            $table->date('expense_date');

            $table->string('payment_method')->nullable();

            $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

            $table->timestamps();

            $table->index('expense_date');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
