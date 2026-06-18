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
        Schema::table('vw_activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_subject_activity_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vw_activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_subject_activity_timeline');
        });
    }
};
