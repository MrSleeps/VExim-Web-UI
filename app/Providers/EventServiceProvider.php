<?php

namespace App\Providers;

use VEximweb\Core\Data\Models\EximUser;
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
use App\Events\DnsRecordCreated;
use App\Events\DnsRecordFailed;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use App\Services\DnsNotificationService;

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

        $dnsNotifier = app(DnsNotificationService::class);

        // When DNS record is created
        Event::listen(DnsRecordCreated::class, function ($event) use ($dnsNotifier) {
            $dnsNotifier->recordCreated($event);
        });

        // When DNS record fails
        Event::listen(DnsRecordFailed::class, function ($event) use ($dnsNotifier) {
            $dnsNotifier->recordFailed($event);
        });       
        
    }
  
}
