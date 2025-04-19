<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up()
{
    Schema::table('webhook_logs', function (Blueprint $table) {
        $table->string('event_type')->default('unknown_event')->change();
    });
}

public function down()
{
    Schema::table('webhook_logs', function (Blueprint $table) {
        $table->string('event_type')->default(null)->change();
    });
}
};
