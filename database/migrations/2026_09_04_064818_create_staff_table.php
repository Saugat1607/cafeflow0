<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            // Link staff member to the existing users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Unique staff identification number
            $table->string('staff_code')->unique();

            // Staff contact information
            $table->string('phone')->nullable();

            // Staff position in the cafe
            $table->string('position')->nullable();

            // active / inactive
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
