<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkeys;
use VEximweb\Core\Data\Models\User;

$tables = [
    'passkeys',
    'vw_role_has_permissions',
    'vw_model_has_roles',
    'vw_model_has_permissions',
    'vw_roles',
    'vw_permissions',
    'vw_activity_log',
    'vw_ccache_locks',
    'vw_cache_locks',
    'vw_cache',
    'vw_sessions',
    'vw_password_reset_tokens',
    'users_web',
];

beforeEach(function () use ($tables) {
    foreach ($tables as $table) {
        Schema::dropIfExists($table);
    }
});

afterEach(function () use ($tables) {
    foreach ($tables as $table) {
        Schema::dropIfExists($table);
    }
});

it('bootstraps the tables required by legacy core migrations', function () {
    $coreMigrations = base_path('vendor/mrsleeps/vexim-web-core-data/database/migrations');

    (require $coreMigrations.'/0001_01_01_000000_create_users_web_table.php')->up();
    (require $coreMigrations.'/0001_01_01_000001_create_cache_table.php')->up();
    (require $coreMigrations.'/2026_05_18_133743_create_permission_tables.php')->up();
    (require $coreMigrations.'/2026_05_18_194201_create_activity_log_table.php')->up();

    expect(Schema::hasTable('users_web'))->toBeTrue()
        ->and(Schema::hasTable('vw_cache'))->toBeTrue()
        ->and(Schema::hasTable('vw_model_has_roles'))->toBeTrue()
        ->and(Schema::hasTable('vw_activity_log'))->toBeTrue()
        ->and(Passkeys::userModel())->toBe(User::class);

    (require $coreMigrations.'/2026_06_16_133952_create_passkeys_table.php')->up();

    expect(Schema::hasTable('passkeys'))->toBeTrue();

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
