<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('vw_settings');
    Schema::dropIfExists('vw_email_template_settings');

    config()->set('settings.repositories.database.table', 'vw_email_template_settings');
});

afterEach(function () {
    Schema::dropIfExists('vw_settings');
    Schema::dropIfExists('vw_email_template_settings');
});

it('creates the Spatie settings schema without taking the core settings table name', function () {
    $migration = require database_path('migrations/2022_12_14_083707_create_settings_table.php');

    $migration->up();

    expect(Schema::hasTable('vw_email_template_settings'))->toBeTrue()
        ->and(Schema::hasColumns('vw_email_template_settings', ['group', 'name', 'locked', 'payload']))->toBeTrue()
        ->and(Schema::hasTable('vw_settings'))->toBeFalse();
});

it('refuses to configure Spatie settings to use the VExim core table', function () {
    config()->set('settings.repositories.database.table', 'vw_settings');

    $migration = require database_path('migrations/2022_12_14_083707_create_settings_table.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'must not use the VExim core vw_settings table');
});

it('repairs the known legacy Spatie collision without dropping its data', function () {
    Schema::create('vw_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('group');
        $table->string('name');
        $table->boolean('locked')->default(false);
        $table->json('payload');
        $table->timestamps();
    });

    DB::table('vw_settings')->insert([
        'group' => 'fin-mail',
        'name' => 'default_locale',
        'locked' => false,
        'payload' => json_encode('en'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_05_18_160000_repair_legacy_spatie_settings_collision.php');
    $migration->up();

    expect(Schema::hasTable('vw_settings'))->toBeFalse()
        ->and(Schema::hasTable('vw_email_template_settings'))->toBeTrue()
        ->and(DB::table('vw_email_template_settings')->where('name', 'default_locale')->exists())->toBeTrue();
});

it('leaves a healthy VExim core settings table untouched', function () {
    Schema::create('vw_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('key')->unique();
        $table->text('value');
        $table->string('type')->default('string');
        $table->text('description')->nullable();
        $table->timestamps();
    });

    DB::table('vw_settings')->insert([
        'key' => 'default_uid',
        'value' => '5000',
        'type' => 'integer',
        'description' => 'Default UID',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_05_18_160000_repair_legacy_spatie_settings_collision.php');
    $migration->up();

    expect(Schema::hasTable('vw_settings'))->toBeTrue()
        ->and(DB::table('vw_settings')->where('key', 'default_uid')->value('value'))->toBe('5000')
        ->and(Schema::hasTable('vw_email_template_settings'))->toBeFalse();
});

it('refuses to alter an unknown settings schema', function () {
    Schema::create('vw_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('unexpected');
    });

    $migration = require database_path('migrations/2026_05_18_160000_repair_legacy_spatie_settings_collision.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'unknown schema');
});
