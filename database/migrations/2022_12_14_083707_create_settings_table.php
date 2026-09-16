<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This migration originally lived in the monolithic application before the
     * V2 package split. Keep the original migration name so existing installs
     * that have already run it do not run it again.
     */
    public function up(): void
    {
        $tableName = config('settings.repositories.database.table', 'vw_email_template_settings');

        if ($tableName === 'vw_settings') {
            throw new \RuntimeException(
                'Spatie/FinMail settings must not use the VExim core vw_settings table.'
            );
        }

        if (Schema::hasTable($tableName)) {
            if (! Schema::hasColumns($tableName, ['group', 'name', 'locked', 'payload'])) {
                throw new \RuntimeException(
                    "Existing {$tableName} table does not have the expected Spatie settings schema."
                );
            }

            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });
    }

    public function down(): void
    {
        $tableName = config('settings.repositories.database.table', 'vw_email_template_settings');

        if ($tableName !== 'vw_settings') {
            Schema::dropIfExists($tableName);
        }
    }
};
