<?php

namespace App\Providers;

use App\Auth\MultiTablePasswordBrokerManager;
use App\Auth\MultiTableUserProvider;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\EximUserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\EximUserRepository;
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