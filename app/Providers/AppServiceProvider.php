<?php

namespace App\Providers;

// use App\Models\Blocklist;
// use App\Models\Setting;
use App\Models\VwPersonalAccessToken;
use App\Services\DKIMKeyService;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;
use Laravel\Sanctum\Sanctum;
use VEximweb\Core\Data\Models\User;
use VEximweb\Core\Data\Models\VwDatabaseNotification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DKIMKeyService::class, function ($app) {
            return new DKIMKeyService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passkeys::useUserModel(User::class);
        Sanctum::usePersonalAccessTokenModel(VwPersonalAccessToken::class);

        RichEditor::configureUsing(function (RichEditor $editor): void {
            $editor->extraAttributes([
                'style' => 'min-height: 650px;',
            ]);
        });   //
        \DB::listen(function ($query) {
            if (str_contains($query->sql, 'delete') && str_contains($query->sql, 'vw_notifications')) {
                \Log::warning('Raw DELETE on vw_notifications', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'trace' => collect(debug_backtrace())->take(15)->map(fn ($t) => ($t['class'] ?? '').($t['type'] ?? '').$t['function'])->implode("\n"),
                ]);
            }
        });

        VwDatabaseNotification::deleting(function ($notification) {
            \Log::warning('VwDatabaseNotification being deleted', [
                'id' => $notification->id,
                'trace' => collect(debug_backtrace())->take(15)->map(fn ($t) => ($t['class'] ?? '').($t['type'] ?? '').$t['function'].' ('.($t['file'] ?? 'unknown').':'.($t['line'] ?? '?').')')->implode("\n"),
            ]);
        });
    }
}
