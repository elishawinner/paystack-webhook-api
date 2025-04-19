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
    

// In the generated migration
Schema::create('webhook_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type');
    $table->string('reference')->nullable();
    $table->json('payload');
    $table->boolean('is_verified');
    $table->text('exception')->nullable();
    $table->timestamps();
    
    $table->index('reference');
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
