<?php

use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach ([
        'vw_role_has_permissions',
        'vw_model_has_roles',
        'vw_model_has_permissions',
        'vw_roles',
        'vw_permissions',
        'vw_activity_log',
        'vw_ccache_locks',
        'vw_cache_locks',
        'vw_cache',
    ] as $table) {
        Schema::dropIfExists($table);
    }
});

afterEach(function () {
    foreach ([
        'vw_role_has_permissions',
        'vw_model_has_roles',
        'vw_model_has_permissions',
        'vw_roles',
        'vw_permissions',
        'vw_activity_log',
        'vw_ccache_locks',
        'vw_cache_locks',
        'vw_cache',
    ] as $table) {
        Schema::dropIfExists($table);
    }
});

it('bootstraps the tables required by the legacy eximuser migration', function () {
    $coreMigrations = base_path('vendor/mrsleeps/vexim-web-core-data/database/migrations');

    (require $coreMigrations.'/0001_01_01_000001_create_cache_table.php')->up();
    (require $coreMigrations.'/2026_05_18_133743_create_permission_tables.php')->up();
    (require $coreMigrations.'/2026_05_18_194201_create_activity_log_table.php')->up();

    expect(Schema::hasTable('vw_cache'))->toBeTrue()
        ->and(Schema::hasTable('vw_model_has_roles'))->toBeTrue()
        ->and(Schema::hasTable('vw_activity_log'))->toBeTrue();

    $cacheLockRepair = require database_path(
        'migrations/2026_09_23_100001_repair_vw_cache_locks_table.php'
    );
    $cacheLockRepair->up();

    expect(Schema::hasTable('vw_cache_locks'))->toBeTrue()
        ->and(Schema::hasTable('vw_ccache_locks'))->toBeFalse();

    $legacyMigration = base_path(
        'vendor/mrsleeps/vexim-web-core-eximuser/database/migrations/0001_migration_from_old_model_to_new_model.php'
    );

    expect(file_exists($legacyMigration))->toBeTrue();

    (require $legacyMigration)->up();

    expect(Schema::hasTable('vw_activity_log'))->toBeTrue()
        ->and(Schema::hasTable('vw_model_has_roles'))->toBeTrue();
});
