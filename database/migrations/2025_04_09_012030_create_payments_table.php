<?php
// database/migrations/xxxx_create_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('email');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('reference');
            $table->index('email');
            $table->index('status');
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};