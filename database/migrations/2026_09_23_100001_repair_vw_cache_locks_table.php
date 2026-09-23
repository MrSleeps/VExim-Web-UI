<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vw_ccache_locks') && ! Schema::hasTable('vw_cache_locks')) {
            Schema::rename('vw_ccache_locks', 'vw_cache_locks');
        }
    }

    public function down(): void
    {
        // Do not restore the misspelt legacy table name.
    }
};
