<?php

namespace App\Filament\Resources\EximAliases\Pages;

use App\Filament\Resources\EximAliases\EximAliasResource;
use App\Services\DomainAdminLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEximAlias extends CreateRecord
{
    protected static string $resource = EximAliasResource::class;
    
    /**
     * Check account limits before the form loads
     */
    public function mount(): void
    {
        $user = auth()->user();
        
        // Check limits for domain admins (not system admins)
        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {
            $limitService = app(DomainAdminLimitService::class);
            $result = $limitService->canCreateAliasAccount($user);
            
            if (!$result['allowed']) {
                Notification::make()
                    ->title('Email Account Limit Reached')
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
     * Check account limits before creating
     */
    protected function beforeCreate(): void
    {
        $user = auth()->user();

        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {
            $limitService = app(DomainAdminLimitService::class);
            
            $formState = $this->form->getState();
            $accountType = $formState['type'] ?? 'local';
            
            $domainId = $formState['domain_id'] ?? null;
            
            if ($accountType === 'local') {
                $result = $limitService->canCreateAliasAccount($user, $domainId);
                
                if (!$result['allowed']) {
                    throw ValidationException::withMessages([
                        'username' => $result['message']
                    ]);
                }
            } elseif ($accountType === 'alias') {
                $result = $limitService->canCreateAliasAccount($user, $domainId);
                
                if (!$result['allowed']) {
                    throw ValidationException::withMessages([
                        'username' => $result['message']
                    ]);
                }
            }
        }
    }
    
    /**
     * Clear cache after successful creation
     */
    protected function afterCreate(): void
    {
        // Clear the domain admin's cache after successful creation
        $user = auth()->user();
        if ($user) {
            $limitService = app(DomainAdminLimitService::class);
            $limitService->clearCache($user);
        }
        
        $formState = $this->form->getState();
        $accountType = $formState['type'] ?? 'local';
        
        $notificationTitle = $accountType === 'local' 
            ? 'Email Account Created Successfully' 
            : 'Alias Account Created Successfully';
        
        Notification::make()
            ->title($notificationTitle)
            ->success()
            ->send();
    }
    
    /**
     * Optional: Validate before form data mutation
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {
            $limitService = app(DomainAdminLimitService::class);
            
            $accountType = $data['type'] ?? 'local';
            $domainId = $data['domain_id'] ?? null;
            
                $result = $limitService->canCreateAliasAccount($user, $domainId);
                
                if (!$result['allowed']) {
                    $this->addError('username', $result['message']);
                    $this->halt();
                }
        }
        
        return $data;
    }    
}
