<?php

namespace App\Providers;

use App\Auth\MultiTablePasswordBrokerManager;
use App\Auth\MultiTableUserProvider;
use VEximweb\Core\Data\Repositories\Interfaces\UserRepositoryInterface;
use VEximweb\Core\Data\Repositories\Interfaces\EximUserRepositoryInterface;
use VEximweb\Core\Data\Repositories\UserRepository;
use VEximweb\Core\Data\Repositories\EximUserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('multi_table', function ($app, array $config) {
            $userRepository = $app->make(UserRepositoryInterface::class);
            $eximUserRepository = $app->make(EximUserRepositoryInterface::class);
            
            return new MultiTableUserProvider($userRepository, $eximUserRepository);
        });

    }
    
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(EximUserRepositoryInterface::class, EximUserRepository::class);
        
        $this->app->singleton('auth.password', function ($app) {
            return new MultiTablePasswordBrokerManager($app);
        });
    }
}