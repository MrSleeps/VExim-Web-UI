<?php

namespace App\Traits;

use Illuminate\Auth\Passwords\CanResetPassword as BaseCanResetPassword;

trait CanResetPassword
{
    use BaseCanResetPassword;
    
    /**
     * Get the e-mail address where password reset links are sent.
     * For EximUser, use the username as the email.
     */
    public function getEmailForPasswordReset()
    {
        return $this->username;
    }
    
    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }
}