<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use MarcelWeidum\Passkeys\PasskeysPlugin;
use Relaticle\ActivityLog\Filament\ActivityLogPlugin;
use Elemind\FilamentECharts\FilamentEChartsPlugin;
use App\Filament\Widgets\AccountTypesChart;
use App\Filament\Widgets\DomainStats;
use App\Filament\Resources\DomainUsers\DomainUserResource; 
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use VEximweb\Core\Data\Models\EximUser;
use FinityLabs\FinMail\FinMailPlugin;
use App\Filament\Pages\Auth\CustomResetPassword;
use App\Filament\Pages\Auth\CustomRequestPasswordReset;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Widgets\RecentLoginActivity;
use Openplain\FilamentShadcnTheme\Color;
use App\Filament\Widgets\SpamStats;

class VeximPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Build plugins array conditionally
        $plugins = [
            PasskeysPlugin::make(),
            ActivityLogPlugin::make(),
            FilamentEChartsPlugin::make(),
            FinMailPlugin::make()->enableSentEmails(false)->navigationGroup('Communications'),
        ];
        
        // Only add FilamentShieldPlugin if SHIELD_VISIBLE is true
        if (env('SHIELD_VISIBLE', false)) {
            $plugins[] = FilamentShieldPlugin::make()
                ->navigationGroup('System Settings');
        }
        
        return $panel
            ->default()
            ->id('vexim')
            ->path('')
            ->viteTheme('resources/css/filament/vexim/theme.css')
            ->login()
            ->authGuard('web')
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('favicon/favicon-96x96.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])          
            ->resources([
                DomainUserResource::class,
            ])
            ->navigationItems([
            ])
            ->navigationGroups([
                'Domain Management',
                'Account Management',
                'Lists',
                'Mailing Lists',
                'Website Management',
                'DNS Management',
                'Communications',
                'System Settings',
                'Reports & Analytics',
            ])           
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DomainStats::class,
                AccountTypesChart::class,
                RecentLoginActivity::class,
            ])
            ->plugins($plugins)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
				\App\Http\Middleware\EnforceTwoFactorAuth::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make(),
            ]);
    }
}