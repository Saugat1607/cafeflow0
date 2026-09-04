<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();

            // Report date
            $table->date('report_date')->unique();

            // Sales
            $table->decimal('total_sales', 12, 2)->default(0);

            // Expenses
            $table->decimal('total_expenses', 12, 2)->default(0);

            // Profit
            $table->decimal('net_profit', 12, 2)->default(0);

            // Counts
            $table->unsignedInteger('total_bills')->default(0);
            $table->unsignedInteger('total_orders')->default(0);

            // Payment method breakdown
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('card_sales', 12, 2)->default(0);
            $table->decimal('esewa_sales', 12, 2)->default(0);
            $table->decimal('other_sales', 12, 2)->default(0);

            // Timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
