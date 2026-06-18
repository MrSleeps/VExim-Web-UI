<?php

namespace App\Filament\Resources\DomainAliases\Pages;

use App\Filament\Resources\DomainAliases\DomainAliasResource;
use App\Services\DomainAdminLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDomainAlias extends CreateRecord
{
    protected static string $resource = DomainAliasResource::class;
    
    /**
     * Check alias domain limits before the form loads
     */
    public function mount(): void
    {
        $user = auth()->user();
        
        // Check limits for domain admins (not system admins)
        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {
            $limitService = app(DomainAdminLimitService::class);
            $result = $limitService->canCreateAliasDomain($user);
            
            if (!$result['allowed']) {
                Notification::make()
                    ->title('Alias Domain Limit Reached')
                    ->body($result['message'])
                    ->danger()
                    ->seconds(5)
                    ->send();
                $this->redirect($this->getResource()::getUrl('index'));
                return;
            }
        }
        parent::mount();
    }
    
    /**
     * Check alias domain limits before creating
     */
    protected function beforeCreate(): void
    {
        $user = auth()->user();
        
        // Only check limits for domain admins (not system admins)
        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {
            $limitService = app(DomainAdminLimitService::class);
            $result = $limitService->canCreateAliasDomain($user);
            
            if (!$result['allowed']) {
                throw ValidationException::withMessages([
                    'alias' => $result['message']
                ]);
            }
        }
    }
    
    /**
     * Clear cache after successful creation
     */
    protected function afterCreate(): void
    {
        $user = auth()->user();
        if ($user) {
            $limitService = app(DomainAdminLimitService::class);
            $limitService->clearCache($user);
        }
        
        Notification::make()
            ->title('Domain Alias Created Successfully')
            ->success()
            ->send();
    }
}