<?php

use App\Channels\FinMailChannel;
use App\Checks\VersionCheck;
use App\Notifications\CustomCheckFailedNotification;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Checks\Result;

it('includes the check name status and failure message in generic health emails', function () {
    config()->set('app.name', 'VExim Web');

    $result = Result::make()
        ->warning('Cache read/write failed.')
        ->check(CacheCheck::new())
        ->endedAt(now());

    $mail = (new CustomCheckFailedNotification([$result]))->toMail();

    expect($mail->subject)->toBe('Health check issue for VExim Web')
        ->and($mail->greeting)->toBe('Health check issue detected')
        ->and($mail->introLines)->toContain('One or more application health checks need attention.')
        ->and($mail->introLines)->toContain('**Cache — WARNING**')
        ->and($mail->introLines)->toContain('Cache read/write failed.');
});

it('uses the dedicated update template when version is the only problem', function () {
    config()->set('health.notifications.mail.to', 'alerts@example.com');

    $versionResult = Result::make()
        ->warning('Update available: 2.1.3 → 2.1.4 (Priority: MEDIUM)')
        ->meta([
            'update_available' => true,
            'current_version' => '2.1.3',
            'latest_version' => '2.1.4',
            'update_priority' => 'medium',
        ])
        ->check(VersionCheck::new())
        ->endedAt(now());

    expect((new CustomCheckFailedNotification([$versionResult]))->via())
        ->toBe([FinMailChannel::class]);
});

it('uses the detailed health email when another problem exists alongside an update', function () {
    config()->set('health.notifications.mail.to', 'alerts@example.com');

    $versionResult = Result::make()
        ->warning('Update available: 2.1.3 → 2.1.4 (Priority: MEDIUM)')
        ->meta([
            'update_available' => true,
            'current_version' => '2.1.3',
            'latest_version' => '2.1.4',
            'update_priority' => 'medium',
        ])
        ->check(VersionCheck::new())
        ->endedAt(now());

    $diskResult = Result::make()
        ->failed('The disk is almost full (95% used).')
        ->check(UsedDiskSpaceCheck::new())
        ->endedAt(now());

    expect((new CustomCheckFailedNotification([$versionResult, $diskResult]))->via())
        ->toBe(['mail']);
});
