<?php

namespace App\Auth;

use VEximweb\Core\Data\Models\EximUser;
use VEximweb\Core\Data\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class MultiTablePasswordBroker extends PasswordBroker
{
    public function sendResetLink(array $credentials, \Closure $callback = null)
    {
        Log::info('Password reset requested', ['credentials' => array_keys($credentials)]);
        
        $user = $this->getUser($credentials);
        
        Log::info('User found?', ['found' => !is_null($user), 'type' => $user ? get_class($user) : 'none']);
        
        if (is_null($user)) {
            return static::INVALID_USER;
        }
        
        if ($this->tokens->recentlyCreatedToken($user)) {
            return static::RESET_THROTTLED;
        }
        
        $this->sendResetLinkInternal($user, $callback);
        
        return static::RESET_LINK_SENT;
    }
    
    protected function getUser(array $credentials)
    {
        $username = $credentials['email'] ?? $credentials['username'] ?? null;
        
        Log::info('Looking for user with identifier', ['identifier' => $username]);
        
        if (!$username) {
            return null;
        }
        
        // Check web users table
        $user = User::where('email', $username)->first();
        if ($user) {
            Log::info('Found user in web users table', ['email' => $username, 'id' => $user->id]);
            return $user;
        }
        
        // Check exim users table
        $eximUser = EximUser::where('username', $username)->first();
        if ($eximUser) {
            Log::info('Found user in exim users table', ['username' => $username, 'id' => $eximUser->user_id]);
            return $eximUser;
        }
        
        Log::warning('User not found in any table', ['identifier' => $username]);
        return null;
    }
    
    public function reset(array $credentials, \Closure $callback)
    {
        $user = $this->validateReset($credentials);
        
        if (!$user instanceof CanResetPasswordContract) {
            return $user;
        }
        
        $password = $credentials['password'];
        
        // Handle password update based on user type
        if ($user instanceof EximUser) {
            // Use EximUser's password setter (creates crypt hash)
            $user->setPasswordAttribute($password);
        } else {
            // Use standard bcrypt for web users
            $user->password = bcrypt($password);
        }
        
        $user->setRememberToken($this->tokens->create($user));
        $user->save();
        
        $callback($user, $password);
        
        $this->tokens->delete($user);
        
        return static::PASSWORD_RESET;
    }
    
    protected function validateReset(array $credentials)
    {
        $user = $this->getUser($credentials);
        
        if (is_null($user)) {
            return static::INVALID_USER;
        }
        
        if (!$this->tokens->exists($user, $credentials['token'])) {
            return static::INVALID_TOKEN;
        }
        
        return $user;
    }
}