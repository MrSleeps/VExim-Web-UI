<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('reconciles an already-created FinMail table whose migration was not recorded', function () {
    $migration = '2099_01_01_000000_create_email_themes_table';
    $path = database_path("migrations/{$migration}.php");

    file_put_contents($path, "<?php\n");

    try {
        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        DB::table('migrations')->where('migration', $migration)->delete();
        Schema::dropIfExists('vw_email_themes');

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
        @unlink($path);
        DB::table('migrations')->where('migration', $migration)->delete();
        Schema::dropIfExists('vw_email_themes');
    }
});

it('refuses to reconcile a FinMail table with an incompatible schema', function () {
    $migration = '2099_01_01_000001_create_email_themes_table';
    $path = database_path("migrations/{$migration}.php");

    file_put_contents($path, "<?php\n");

    try {
        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        DB::table('migrations')->where('migration', $migration)->delete();
        Schema::dropIfExists('vw_email_themes');

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
        @unlink($path);
        DB::table('migrations')->where('migration', $migration)->delete();
        Schema::dropIfExists('vw_email_themes');
    }
});
