<?php
namespace App\Channels;

use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class FinMailChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFinMail')) {
            return;
        }

        $mailable = $notification->toFinMail($notifiable);

        if ($mailable === null) {
            return;
        }

        Mail::send($mailable);
    }
}
