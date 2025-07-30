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
        Schema::create('order_item_measurements', function (Blueprint $table) {
            $table->id();
        $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
        $table->foreignId('measurement_id')->constrained('measurements')->onDelete('cascade');
        $table->string('value');
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order__item__measurements');
    }
};
