<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function ensureMigrationsTableExists(): void
{
    if (Schema::hasTable('migrations')) {
        return;
    }

    Schema::create('migrations', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
    });
}

it('reconciles an already-created canonical FinMail table whose migration was not recorded', function () {
    $migration = '2026_05_21_143400_create_email_themes_table';

    ensureMigrationsTableExists();

    $existingMigration = DB::table('migrations')->where('migration', $migration)->first();

    DB::table('migrations')->where('migration', $migration)->delete();
    Schema::dropIfExists('vw_email_themes');

    try {
        Schema::create('vw_email_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->json('colors');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $this->artisan('vw:repair-setup-migrations')
            ->expectsOutputToContain("Reconciled {$migration}")
            ->assertSuccessful();

        expect(
            DB::table('migrations')->where('migration', $migration)->exists()
        )->toBeTrue();
    } finally {
        DB::table('migrations')->where('migration', $migration)->delete();

        if ($existingMigration !== null) {
            DB::table('migrations')->insert((array) $existingMigration);
        }

        Schema::dropIfExists('vw_email_themes');
    }
});

it('refuses to reconcile a canonical FinMail table with an incompatible schema', function () {
    $migration = '2026_05_21_143400_create_email_themes_table';

    ensureMigrationsTableExists();

    $existingMigration = DB::table('migrations')->where('migration', $migration)->first();

    DB::table('migrations')->where('migration', $migration)->delete();
    Schema::dropIfExists('vw_email_themes');

    try {
        Schema::create('vw_email_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
        });

        $this->artisan('vw:repair-setup-migrations')
            ->expectsOutputToContain("Cannot reconcile {$migration}")
            ->assertFailed();

        expect(
            DB::table('migrations')->where('migration', $migration)->exists()
        )->toBeFalse();
    } finally {
        DB::table('migrations')->where('migration', $migration)->delete();

        if ($existingMigration !== null) {
            DB::table('migrations')->insert((array) $existingMigration);
        }

        Schema::dropIfExists('vw_email_themes');
    }
});
