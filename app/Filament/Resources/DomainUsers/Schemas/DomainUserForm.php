<?php

namespace App\Filament\Resources\DomainUsers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Log;

class DomainUserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('Update your email account settings')
                    ->schema([
                        TextInput::make('realname')
                            ->label('Display Name')
                            ->placeholder('Your full name')
                            ->maxLength(255)
                            ->helperText('This name will appear on outgoing emails'),
                        
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->placeholder('Leave blank to keep current password')
                            ->maxLength(255)
                            ->helperText('Password must be at least 8 characters')
                            ->rule('min:8')
                            ->dehydrateStateUsing(function ($state, $record) {
                                if (empty($state)) {
                                    return null;
                                }

                                $hashed = password_hash($state, PASSWORD_BCRYPT, ['cost' => 12]);
                                
                                Log::info('Password hashed in form', [
                                    'plain_length' => strlen($state),
                                    'hash_prefix' => substr($hashed, 0, 10)
                                ]);
                                
                                // Create activity log for password change
                                try {
                                    $user = auth()->user();
                                    $record = $record ?? $user;
                                    
                                    if ($record) {
                                        activity()
                                            ->performedOn($record)
                                            ->causedBy($user)
                                            ->withProperties([
                                                'event' => 'password_change',
                                                'user_identifier' => $record->username ?? $user->username,
                                                'changed_at' => now()->toDateTimeString(),
                                                'ip' => request()->ip(),
                                                'user_agent' => request()->userAgent()
                                            ])
                                            ->event('password_updated')
                                            ->log('Password was changed for ' . ($record->username ?? $user->username));
                                        
                                        Log::info('Activity log created for password change from form');
                                    } else {
                                        Log::warning('No record available for activity logging');
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to create activity log: ' . $e->getMessage());
                                }
                                
                                return $hashed;
                            }),
                        
                        TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->same('password')
                            ->placeholder('Confirm your new password')
                            ->dehydrateStateUsing(fn ($state) => null), // Don't save this field
                    ])->columns(2),
                
                Section::make('Account Details')
                    ->description('Your account information (read-only)')
                    ->schema([
                        TextInput::make('username')
                            ->label('Email Address')
                            ->disabled()
                            ->default($user->username),
                        
                        TextInput::make('quota')
                            ->label('Quota (MB)')
                            ->disabled()
                            ->default($user->quota),
                        
                        TextInput::make('enabled')
                            ->label('Status')
                            ->disabled()
                            ->default($user->enabled ? 'Active' : 'Disabled'),
                    ])->columns(2),
            ]);
    }
}