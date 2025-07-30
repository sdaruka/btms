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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
        $table->int('order_number')->unique();
        $table->date('order_date')->default(now());
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->unsignedBigInteger('assignedto')->nullable()->references('id')->on('users')->onDelete('set null');
        // $table->foreign('assignedto')->references('id')->on('users')->onDelete('set null');
        $table->date('delivery_date')->nullable();
        $table->decimal('discount',12,2)->default(0);
        $table->decimal('received',12,2)->default(0);
        $table->decimal('design_charge', 12,2)->default(0);
        $table->decimal('total_amount',12,2)->default(0);
        $table->string('remarks', 255)->nullable();
        $table->string('status')->default('Pending');
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
