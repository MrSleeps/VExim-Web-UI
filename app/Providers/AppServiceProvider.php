<?php

namespace App\Providers;

use App\Models\Domain;
use App\Models\EximUser;
use App\Models\DomainAlias;
use App\Models\Blocklist;
use App\Models\Setting;
use App\Models\User;
use App\Observers\GlobalActivityObserver;
use Illuminate\Support\ServiceProvider;
use App\Auth\MultiTableUserProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\DKIMKeyService;
use Filament\Forms\Components\RichEditor;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log; 
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DKIMKeyService::class, function ($app) {
            return new DKIMKeyService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {   
        Sanctum::usePersonalAccessTokenModel(\App\Models\VwPersonalAccessToken::class);
        
        RichEditor::configureUsing(function (RichEditor $editor): void {
            $editor->extraAttributes([
                'style' => 'min-height: 650px;',
            ]);
        });           
    }
}