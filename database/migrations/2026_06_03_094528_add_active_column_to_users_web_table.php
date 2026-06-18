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
        Schema::table('users_web', function (Blueprint $table) {
            // Add active column with default value true for existing users
            $table->boolean('active')->default(true)->after('email_verified_at');
            $table->timestamp('deactivated_at')->nullable()->after('active');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_web', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['active']);
            
            // Then drop the column
            $table->dropColumn('active');
            $table->dropColumn('deactivated_at');
        });
    }
};