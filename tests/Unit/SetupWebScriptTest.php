<?php

it('prepares all third-party migrations before running the web migrations', function () {
    $script = file_get_contents(base_path('setup-web.sh'));

    expect($script)
        ->toContain('set -eEo pipefail')
        ->toContain('php artisan vendor:publish --tag="fin-mail-migrations"')
        ->toContain('php artisan vw:repair-setup-migrations')
        ->toContain('bootstrap_legacy_core_migration_prerequisites')
        ->toContain('0001_01_01_000001_create_cache_table.php')
        ->toContain('2026_05_18_133743_create_permission_tables.php')
        ->toContain('2026_05_18_194201_create_activity_log_table.php')
        ->not->toContain('Spatie\\Activitylog\\ActivitylogServiceProvider');

    $finMailPublish = strpos($script, 'php artisan vendor:publish --tag="fin-mail-migrations"');
    $repair = strpos($script, 'php artisan vw:repair-setup-migrations', $finMailPublish);
    $bootstrap = strpos($script, 'bootstrap_legacy_core_migration_prerequisites', $repair);
    $webMigrate = strpos($script, 'php artisan migrate --force', $bootstrap);

    expect($finMailPublish)->not->toBeFalse()
        ->and($repair)->not->toBeFalse()
        ->and($bootstrap)->not->toBeFalse()
        ->and($webMigrate)->not->toBeFalse()
        ->and($finMailPublish)->toBeLessThan($repair)
        ->and($repair)->toBeLessThan($bootstrap)
        ->and($bootstrap)->toBeLessThan($webMigrate);
});
