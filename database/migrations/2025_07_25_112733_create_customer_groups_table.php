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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., VIP, Regular, Wholesale
            $table->integer('discount')->nullable();
            $table->decimal('rate', 10, 2)->nullable();; // Special Rate for the group
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // Foreign key to products table
            $table->date('effective_date')->nullable(); // Date when the rate becomes effective
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
