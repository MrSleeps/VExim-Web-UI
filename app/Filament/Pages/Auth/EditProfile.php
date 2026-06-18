<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Callout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        // Get the currently authenticated user
        $user = Auth::user();
        
        // Prepare components array
        $components = [];
        
        // Add 2FA/Passkey warning if needed
        if ($user instanceof \App\Models\User && !$this->hasTwoFactorEnabled($user)) {
            // Check if 2FA is enforced by system admin
            $isEnforced = config('vexim.website.admin_enforce_2fa', false);
            
            if ($isEnforced) {
                $components[] = Callout::make('Security Requirement')
                    ->description('The system administrator requires multi-factor authentication to be enabled for your account. Please scroll down to the "Passkey Verification" section below and set up a passkey.')
                    ->danger()
                    ->icon('heroicon-o-shield-exclamation');
            } else {
                $components[] = Callout::make('Security Recommendation')
                    ->description('Multi-factor authentication is not enabled for your account. We strongly recommend setting up a passkey in the "Passkey Verification" section below to enhance your account security.')
                    ->warning()
                    ->icon('heroicon-o-shield-exclamation');
            }
        }
        
        // Determine which fields to show based on user type
        if ($user instanceof \App\Models\EximUser) {
            // Domain user - use EximUser columns
            $components = array_merge($components, [
                TextInput::make('realname')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('username')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
        } else {
            // Regular web user - use standard columns
            $components = array_merge($components, [
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
        }
        
        return $schema->components($components);
    }
    
    /**
     * Check if the user has any MFA/passkey enabled.
     * Since you're using spatie/laravel-passkeys, this checks the passkeys table.
     */
    protected function hasTwoFactorEnabled($user): bool
    {
        return $user->passkeys()->exists();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof \App\Models\EximUser) {
            // Map form fields to EximUser columns
            $updateData = [];
            if (isset($data['realname'])) {
                $updateData['realname'] = $data['realname'];
            }
            if (isset($data['username'])) {
                $updateData['username'] = $data['username'];
            }
            if (isset($data['password'])) {
                $updateData['crypt'] = bcrypt($data['password']);
            }
            
            $record->update($updateData);
            return $record;
        }
        
        // Regular user update
        $record->update($data);
        return $record;
    }
}