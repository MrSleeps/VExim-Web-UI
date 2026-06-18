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
            $table->unsignedInteger('max_domains')->nullable();
            $table->unsignedInteger('max_alias_domains')->nullable();
            $table->unsignedInteger('max_accounts')->nullable()->after('max_alias_domains');
            $table->unsignedInteger('max_alias_accounts')->nullable()->after('max_accounts');
	    $table->unsignedInteger('max_quota')->nullable()->after('max_alias_accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_web', function (Blueprint $table) {
            $table->dropColumn('max_domains');
            $table->dropColumn('max_alias_domains');
            $table->dropColumn('max_accounts');
            $table->dropColumn('max_alias_accounts');
            $table->dropColumn('max_quota');
        });
    }
};
