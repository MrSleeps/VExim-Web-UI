<?php

it('uses canonical FinMail migrations before running the web migrations', function () {
    $script = file_get_contents(base_path('setup-web.sh'));

    expect($script)
        ->toContain('set -eEo pipefail')
        ->toContain('cleanup_duplicate_finmail_migrations')
        ->toContain('php artisan vw:repair-setup-migrations')
        ->toContain('bootstrap_legacy_core_migration_prerequisites')
        ->toContain('0001_01_01_000001_create_cache_table.php')
        ->toContain('2026_05_18_133743_create_permission_tables.php')
        ->toContain('2026_05_18_194201_create_activity_log_table.php')
        ->not->toContain('php artisan vendor:publish --tag="fin-mail-migrations"')
        ->not->toContain('Spatie\\Activitylog\\ActivitylogServiceProvider');

    foreach ([
        '2026_05_21_143400_create_email_themes_table.php',
        '2026_05_21_143401_create_email_templates_table.php',
        '2026_05_21_143402_create_email_template_versions_table.php',
        '2026_05_21_143403_create_sent_emails_table.php',
        '2026_05_21_143404_add_reply_to_on_email_templates_table.php',
    ] as $migration) {
        expect(file_exists(database_path('migrations/'.$migration)))->toBeTrue();
    }

    $cleanup = strpos($script, 'cleanup_duplicate_finmail_migrations', strpos($script, 'main_setup()'));
    $repair = strpos($script, 'php artisan vw:repair-setup-migrations', $cleanup);
    $bootstrap = strpos($script, 'bootstrap_legacy_core_migration_prerequisites', $repair);
    $webMigrate = strpos($script, 'php artisan migrate --force', $bootstrap);

    expect($cleanup)->not->toBeFalse()
        ->and($repair)->not->toBeFalse()
        ->and($bootstrap)->not->toBeFalse()
        ->and($webMigrate)->not->toBeFalse()
        ->and($cleanup)->toBeLessThan($repair)
        ->and($repair)->toBeLessThan($bootstrap)
        ->and($bootstrap)->toBeLessThan($webMigrate);
});
