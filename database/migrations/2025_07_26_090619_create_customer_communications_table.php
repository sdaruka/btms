// database/migrations/YYYY_MM_DD_HHMMSS_create_customer_communications_table.php

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
        Schema::create('customer_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The customer this communication is about
            $table->string('type')->comment('e.g., call, email, message, in-person, other'); // Type of communication
            $table->string('subject')->nullable(); // Short subject line
            $table->text('content'); // Detailed content of the communication
            $table->foreignId('logged_by_user_id')->constrained('users')->onDelete('restrict'); // The user (staff) who logged this communication

            $table->timestamps(); // created_at (when logged), updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_communications');
    }
};