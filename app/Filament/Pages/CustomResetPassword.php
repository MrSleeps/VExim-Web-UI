<?php

namespace App\Filament\Pages;

use App\Models\EximUser;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class CustomResetPassword extends ResetPassword
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('email')
                    ->label('Email address or username')
                    ->required(),

                TextInput::make('password')
                    ->label('New password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->same('password_confirmation'),

                TextInput::make('password_confirmation')
                    ->label('Confirm new password')
                    ->password()
                    ->required()
                    ->minLength(8),
            ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $data = $this->form->getState();
        $token = $this->token ?? request()->route('token');
        
        Log::info('=== PASSWORD RESET ===', [
            'email' => $data['email'],
            'password_is_hash' => str_starts_with($data['password'], '$2y$'),
            'password_prefix' => substr($data['password'], 0, 10)
        ]);

        $user = User::where('email', $data['email'])->first()
            ?? EximUser::where('username', $data['email'])->first();

        if (! $user || ! Password::broker()->getRepository()->exists($user, $token)) {
            Notification::make()
                ->title('Invalid reset token or email address.')
                ->danger()
                ->send();

            return null;
        }

        // Filament already sends a bcrypt hash, so store it directly without re-hashing
        if ($user instanceof EximUser) {
            // Store the already-hashed password directly
            $user->crypt = $data['password'];
            Log::info('EximUser password stored (already hashed by Filament)');
        } else {
            // Web users: Filament also sends a bcrypt hash, store directly
            $user->password = $data['password'];
            Log::info('WebUser password stored (already hashed by Filament)');
        }

        $user->save();
        Password::broker()->deleteToken($user);

        Notification::make()
            ->title('Password reset successfully!')
            ->success()
            ->send();

        return app(PasswordResetResponse::class);
    }
}