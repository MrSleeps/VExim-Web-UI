<?php

use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('vw_activity_log');
});

afterEach(function () {
    Schema::dropIfExists('vw_activity_log');
});

it('creates the VExim activity log table used by the custom activity model', function () {
    $migration = require database_path('migrations/2026_06_23_120000_create_vw_activity_log_table.php');

    $migration->up();

    expect(Schema::hasTable('vw_activity_log'))->toBeTrue()
        ->and(Schema::hasColumns('vw_activity_log', [
            'id',
            'log_name',
            'description',
            'subject_type',
            'subject_id',
            'event',
            'causer_type',
            'causer_id',
            'attribute_changes',
            'properties',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('is safe to run when the VExim activity log table already exists', function () {
    $migration = require database_path('migrations/2026_06_23_120000_create_vw_activity_log_table.php');

    $migration->up();

    $migration->up();

    expect(Schema::hasTable('vw_activity_log'))->toBeTrue();
});
