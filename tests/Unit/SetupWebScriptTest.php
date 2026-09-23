<?php

it('prepares all third-party migrations before running the web migrations', function () {
    $script = file_get_contents(base_path('setup-web.sh'));

    expect($script)
        ->toContain('set -eEo pipefail')
        ->toContain('php artisan vendor:publish --tag="fin-mail-migrations"')
        ->not->toContain('Spatie\\Activitylog\\ActivitylogServiceProvider');

    $finMailPublish = strpos($script, 'php artisan vendor:publish --tag="fin-mail-migrations"');
    $webMigrate = strpos($script, 'php artisan migrate --force', $finMailPublish);

    expect($finMailPublish)->not->toBeFalse()
        ->and($webMigrate)->not->toBeFalse()
        ->and($finMailPublish)->toBeLessThan($webMigrate);
});
