<?php

namespace App\Providers;

use App\Models\EximUser;
use App\Observers\EximUserObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use Spatie\Health\Events\CheckFailedEvent;
use App\Listeners\SendVersionUpdateEmail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
        Logout::class => [
            LogLogout::class,
        ],
        CheckFailedEvent::class => [
            SendVersionUpdateEmail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
        
    }
}
