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
        Schema::table('vw_settings', function (Blueprint $table) {
            $table->boolean('can_delete')->default(false)->after('description');
            $table->boolean('system_setting')->default(false)->after('can_delete');
            $table->boolean('web_setting')->default(false)->after('system_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vw_settings', function (Blueprint $table) {
            $table->dropColumn(['can_delete', 'system_setting', 'web_setting']);
        });
    }
};
