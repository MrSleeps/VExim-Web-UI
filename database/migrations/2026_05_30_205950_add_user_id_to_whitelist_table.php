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
        Schema::table('whitelist_senders', function (Blueprint $table) {
            // Add user_id column after domain_id (matching blocklists structure)
            $table->unsignedInteger('user_id')->nullable()->after('domain_id');
            
            // Add foreign key constraint to users.user_id
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Add foreign key constraint to domains.domain_id (if not already exists)
            // Check if foreign key exists first, if not, add it
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whitelist_senders', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['user_id']);
            $table->dropForeign(['domain_id']);
            
            // Drop the column
            $table->dropColumn('user_id');
        });
    }
};
