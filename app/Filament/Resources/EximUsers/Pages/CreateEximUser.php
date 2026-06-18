<?php

namespace App\Filament\Resources\EximUsers\Pages;

use App\Filament\Resources\EximUsers\EximUserResource;
use App\Services\DomainAdminLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Models\Domain;
use App\Models\Setting;

class CreateEximUser extends CreateRecord
{
    protected static string $resource = EximUserResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->isDomainAdmin() && !$user->isSystemAdmin()) {

            $limitService = app(DomainAdminLimitService::class);
            $result = $limitService->canCreateEmailAccount($user);

            if (! $result['allowed']) {

                Notification::make()
                    ->title('Email Account Limit Reached')
                    ->body($result['message'])
                    ->danger()
                    ->persistent()
                    ->send();

                $this->redirect($this->getResource()::getUrl('index'));
                return;
            }
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isDomainAdmin() || $user->isSystemAdmin()) {
            return;
        }

        $data = $this->form->getState();

        $limitService = app(DomainAdminLimitService::class);

        $result = $limitService->checkDomainAccountLimit($data['domain_id'] ?? null);

        if (! $result['allowed']) {
            throw ValidationException::withMessages([
                'domain_id' => $result['message'],
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if ($user) {
            app(DomainAdminLimitService::class)->clearCache($user);
        }

        Notification::make()
            ->title('Email Account Created Successfully')
            ->success()
            ->send();
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['username']) && !empty($data['localpart']) && !empty($data['domain_id'])) {
            $domain = Domain::find($data['domain_id']);
            if ($domain) {
                $data['username'] = $data['localpart'] . '@' . $domain->domain;
                \Illuminate\Support\Facades\Log::info('Username was missing, generated: ' . $data['username']);
            }
        }

        if (empty($data['smtp']) && !empty($data['localpart']) && !empty($data['domain_id'])) {
            $mailRoot = Setting::get('mail_root', '/var/mail/vexim');
            $mailRoot = rtrim($mailRoot, '/');
            $domain = Domain::find($data['domain_id']);
            if ($domain) {
                $data['smtp'] = $mailRoot . '/' . $domain->domain . '/' . $data['localpart'] . '/Maildir';
                $data['pop'] = $mailRoot . '/' . $domain->domain . '/' . $data['localpart'];
            }
        }

        return $data;
    }    
}