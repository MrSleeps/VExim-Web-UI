<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vw_activity_log', function (Blueprint $table) {
            // Change subject_id to string to handle both integers and strings
            $table->string('subject_id', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vw_activity_log', function (Blueprint $table) {
            $table->integer('subject_id')->change();
        });
    }
};
