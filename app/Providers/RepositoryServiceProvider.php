<?php

namespace App\Providers;

use App\Repositories\Interfaces\DomainAliasRepositoryInterface;
use App\Repositories\DomainAliasRepository;
use App\Repositories\Interfaces\DomainRepositoryInterface;
use App\Repositories\DomainRepository;
use App\Repositories\Interfaces\DomainStatsRepositoryInterface;
use App\Repositories\DomainStatsRepository;
use App\Repositories\Interfaces\DomainUserRepositoryInterface;
use App\Repositories\DomainUserRepository;
use App\Repositories\Interfaces\EmailScoreSampleRepositoryInterface;
use App\Repositories\EmailScoreSampleRepository;
use App\Repositories\Interfaces\EmailStatRepositoryInterface;
use App\Repositories\EmailStatRepository;
use App\Repositories\Interfaces\EximUserRepositoryInterface;
use App\Repositories\EximUserRepository;
use App\Repositories\Interfaces\RecipientStatsRepositoryInterface;
use App\Repositories\RecipientStatsRepository;
use App\Repositories\Interfaces\SenderDomainStatsRepositoryInterface;
use App\Repositories\SenderDomainStatsRepository;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use App\Repositories\SettingRepository;
use App\Repositories\Interfaces\SpamRuleScoreSampleRepositoryInterface;
use App\Repositories\SpamRuleScoreSampleRepository;
use App\Repositories\Interfaces\SpamRuleStatRepositoryInterface;
use App\Repositories\SpamRuleStatRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

use App\Services\EmailSpamStatsService;
use App\Services\PermissionAwareStatsService;
use App\Repositories\Interfaces\MailmanListRepositoryInterface;
use App\Repositories\MailmanListRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        $this->app->bind(DomainAliasRepositoryInterface::class, DomainAliasRepository::class);
        $this->app->bind(DomainRepositoryInterface::class, DomainRepository::class);
        $this->app->bind(DomainStatsRepositoryInterface::class, DomainStatsRepository::class);
        $this->app->bind(DomainUserRepositoryInterface::class, DomainUserRepository::class);
        $this->app->bind(EmailScoreSampleRepositoryInterface::class, EmailScoreSampleRepository::class);
        $this->app->bind(EmailStatRepositoryInterface::class, EmailStatRepository::class);
        $this->app->bind(EximUserRepositoryInterface::class, EximUserRepository::class);
        $this->app->bind(RecipientStatsRepositoryInterface::class, RecipientStatsRepository::class);
        $this->app->bind(SenderDomainStatsRepositoryInterface::class, SenderDomainStatsRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(SpamRuleScoreSampleRepositoryInterface::class, SpamRuleScoreSampleRepository::class);
        $this->app->bind(SpamRuleStatRepositoryInterface::class, SpamRuleStatRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->singleton(PermissionAwareStatsService::class, function ($app) {
            return new PermissionAwareStatsService(
                $app->make(EmailSpamStatsService::class),
                $app->make(DomainStatsRepositoryInterface::class),
                $app->make(RecipientStatsRepositoryInterface::class),
                $app->make(SenderDomainStatsRepositoryInterface::class),
                $app->make(EmailStatRepositoryInterface::class)
            );
        });
        $this->app->bind(
            MailmanListRepositoryInterface::class,
            MailmanListRepository::class
        );
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}