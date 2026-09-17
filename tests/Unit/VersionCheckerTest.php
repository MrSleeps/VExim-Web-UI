<?php

use App\Services\VersionChecker;

it('uses a configured version override when one is provided', function () {
    config()->set('vexim.package.version', 'v2.3.4');

    expect((new VersionChecker)->getCurrentVersion())->toBe('2.3.4');
});

it('assigns update priority from the current and target release channels', function (string $current, string $latest, string $expected) {
    $checker = new VersionChecker;

    expect($checker->getUpdatePriority($current, $latest))->toBe($expected);
})->with([
    'beta to stable is high priority' => ['1.2.0-beta.2', '1.2.0', 'high'],
    'stable to stable is medium priority' => ['1.1.0', '1.2.0', 'medium'],
    'beta to newer beta is low priority' => ['1.2.0-beta.1', '1.2.0-beta.2', 'low'],
    'stable to beta is ignored' => ['1.1.0', '1.2.0-beta.1', 'ignore'],
]);
