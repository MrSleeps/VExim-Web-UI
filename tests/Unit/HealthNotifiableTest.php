<?php

use App\Notifications\HealthNotifiable;
use App\Services\HealthNotificationRecipients;

it('uses the configured health email as an override', function () {
    config()->set('health.notifications.mail.to', 'alerts@example.com');

    expect(HealthNotificationRecipients::mail())->toBe(['alerts@example.com'])
        ->and((new HealthNotifiable)->routeNotificationForMail())->toBe('alerts@example.com');
});

it('normalizes configured health email arrays', function () {
    config()->set('health.notifications.mail.to', [
        ' first@example.com ',
        '',
        'first@example.com',
        'second@example.com',
    ]);

    expect(HealthNotificationRecipients::mail())->toBe([
        'first@example.com',
        'second@example.com',
    ]);
});
