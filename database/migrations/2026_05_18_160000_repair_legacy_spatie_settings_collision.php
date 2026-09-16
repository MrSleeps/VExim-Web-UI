<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair the one known bad state created by the historical package split:
     * the Spatie settings schema was created as `vw_settings`, blocking the
     * real VExim core settings migration that follows this one.
     */
    public function up(): void
    {
        if (! Schema::hasTable('vw_settings')) {
            return;
        }

        // A healthy VExim settings table needs no repair.
        if (Schema::hasColumns('vw_settings', ['key', 'value', 'type', 'description'])) {
            return;
        }

        if (! Schema::hasColumns('vw_settings', ['group', 'name', 'locked', 'payload'])) {
            throw new \RuntimeException(
                'Existing vw_settings table has an unknown schema; refusing to modify it automatically.'
            );
        }

        $finMailTable = config('settings.repositories.database.table', 'vw_email_template_settings');

        if ($finMailTable === 'vw_settings') {
            throw new \RuntimeException(
                'Spatie/FinMail settings are configured to use vw_settings; this conflicts with VExim core settings.'
            );
        }

        if (Schema::hasTable($finMailTable)) {
            throw new \RuntimeException(
                "Both legacy Spatie data in vw_settings and {$finMailTable} already exist; manual reconciliation is required."
            );
        }

        Schema::rename('vw_settings', $finMailTable);
    }

    /**
     * Intentionally non-destructive. Reversing an automatic recovery could move
     * live FinMail settings back over the VExim core settings table name.
     */
    public function down(): void
    {
        // No automatic rollback for recovered data.
    }
};
