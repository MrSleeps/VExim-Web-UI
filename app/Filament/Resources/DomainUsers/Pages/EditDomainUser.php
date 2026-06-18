<?php

namespace App\Filament\Resources\DomainUsers\Pages;

use App\Filament\Resources\DomainUsers\DomainUserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;

class EditDomainUser extends EditRecord
{
    protected static string $resource = DomainUserResource::class;
    
    public function getRecord(): Model
    {
        // Always return the logged-in user
        $user = auth()->user();
        
        if (!$user instanceof \App\Models\EximUser) {
            abort(403, 'Unauthorized access.');
        }
        
        return $user;
    }
    
    protected function getRedirectUrl(): string
    {
        // Stay on the same page after save
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
    
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Account updated')
            ->body('Your account has been updated successfully.');
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Don't show password fields
        unset($data['password']);
        unset($data['password_confirmation']);
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Check if password is being changed
        $passwordChanged = isset($data['password']) && !empty($data['password']);
        
        // Update the record
        $record->update($data);

        // If password was changed, send Fin-Mail template
        if ($passwordChanged) {
            Mail::to($record->email)->send(
                TemplateMail::make('user-password-changed')
                    ->models(['user' => $record])
            );
            
            // Re-login the user to maintain session
            auth()->login($record);
        }

        return $record;
    }    
    
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
}