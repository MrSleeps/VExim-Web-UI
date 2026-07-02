<?php

namespace App\Providers;

use App\Repositories\Interfaces\DomainUserRepositoryInterface;
use App\Repositories\DomainUserRepository;
//use App\Repositories\Interfaces\UserRepositoryInterface;
use VEximweb\Core\Data\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;
//use App\Services\EmailSpamStatsService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {


        $this->app->bind(DomainUserRepositoryInterface::class, DomainUserRepository::class);
        /*
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        $this->app->bind(
            MailmanListRepositoryInterface::class,
            MailmanListRepository::class
        );
        */
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}