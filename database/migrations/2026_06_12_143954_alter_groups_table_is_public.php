<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('groups')
            ->where('is_public', 'Y')
            ->update(['is_public' => 1]);
        
        DB::table('groups')
            ->where('is_public', 'N')
            ->update(['is_public' => 0]);
        
        Schema::table('groups', function (Blueprint $table) {
            $table->tinyInteger('is_public')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to char(1)
        Schema::table('groups', function (Blueprint $table) {
            $table->char('is_public', 1)->default('Y')->change();
        });
        
        DB::table('groups')
            ->where('is_public', 1)
            ->update(['is_public' => 'Y']);
        
        DB::table('groups')
            ->where('is_public', 0)
            ->update(['is_public' => 'N']);
    }
};
