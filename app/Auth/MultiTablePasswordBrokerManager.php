<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use InvalidArgumentException;

class MultiTablePasswordBrokerManager extends PasswordBrokerManager
{
    protected function createBroker(array $config)
    {
        $provider = $this->app['auth']->createUserProvider($config['provider'] ?? null);
        
        if ($provider === null) {
            throw new InvalidArgumentException('Unable to resolve user provider for password broker.');
        }
        
        return new MultiTablePasswordBroker(
            $provider,
            $this->app['hash'],
            $this->app['events'],
            $config
        );
    }
}