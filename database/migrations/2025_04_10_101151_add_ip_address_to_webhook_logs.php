<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            // Add ip_address column if it doesn't exist
            if (!Schema::hasColumn('webhook_logs', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('headers');
            }
            
            // Add any other missing columns
            if (!Schema::hasColumn('webhook_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('ip_address');
            }
        });
    }

    public function down()
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'metadata']);
        });
    }
};