<?php

namespace App\Auth;

use App\Models\User;
use App\Models\EximUser;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\EximUserRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MultiTableUserProvider implements UserProvider
{
    protected UserRepositoryInterface $userRepository;
    protected EximUserRepositoryInterface $eximUserRepository;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        EximUserRepositoryInterface $eximUserRepository
    ) {
        $this->userRepository = $userRepository;
        $this->eximUserRepository = $eximUserRepository;
    }
    
    public function getModel()
    {
        return User::class;
    }    
    
    public function retrieveById($identifier)
    {
        // Use repository to find only active users
        $user = $this->userRepository->findActiveById($identifier);
        if ($user) {
            return $user;
        }
        
        // Use repository to find only enabled exim users
        $eximUser = $this->eximUserRepository->findEnabledById($identifier);
        if ($eximUser) {
            return $eximUser;
        }
        
        return null;
    }    
    
    public function retrieveByToken($identifier, $token)
    {
        // Use repository to find active user by remember token
        $user = $this->userRepository->findActiveByRememberToken($token);
        if ($user) {
            return $user;
        }
        
        // Use repository to find enabled exim user by remember token
        $eximUser = $this->eximUserRepository->findEnabledByRememberToken($token);
        if ($eximUser) {
            // Assign domain_user role if not already assigned
            if (!$eximUser->hasRole('domain_user')) {
                $eximUser->assignRole('domain_user');
            }
            return $eximUser;
        }
        
        return null;
    }
    
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }
    
    public function retrieveByCredentials(array $credentials)
    {
        Log::info('Step 1: retrieveByCredentials called', [
            'credentials_keys' => array_keys($credentials)
        ]);
        
        $username = $credentials['email'] ?? $credentials['username'] ?? null;
        
        Log::info('Step 2: Looking for username', ['username' => $username]);
        
        if (!$username) {
            return null;
        }
        
        // Use repository to find only active web users
        $user = $this->userRepository->findActiveByEmail($username);
        if ($user) {
            Log::info('Step 3: Found active web user', ['id' => $user->id]);
            return $user;
        }
        
        // Use repository to find only enabled exim users
        $eximUser = $this->eximUserRepository->findEnabledByUsername($username);
        if ($eximUser) {
            Log::info('Step 3: Found enabled exim user', [
                'user_id' => $eximUser->user_id, 
                'username' => $eximUser->username
            ]);
            
            // Assign domain_user role if not already assigned
            if (!$eximUser->hasRole('domain_user')) {
                $eximUser->assignRole('domain_user');
            }
            return $eximUser;
        }
        
        Log::warning('Step 3: Active/Enabled user not found');
        return null;
    }
    
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        Log::info('Step 4: validateCredentials called', [
            'user_type' => get_class($user),
            'username' => $user->username ?? $user->email ?? 'unknown'
        ]);
        
        $password = $credentials['password'];
        
        if ($user instanceof EximUser) {
            // Double-check that user is still enabled (could have been disabled after retrieval)
            if ($user->enabled != 1) {
                Log::warning('Step 4.5: EximUser is disabled', ['user_id' => $user->user_id]);
                return false;
            }
            $result = $user->verifyPassword($password);
            Log::info('Step 5: EximUser verification result', ['result' => $result]);
            return $result;
        }
        
        if ($user instanceof User) {
            // Double-check that user is still active (could have been deactivated after retrieval)
            if ($user->active != 1) {
                Log::warning('Step 4.5: Web user is inactive', ['id' => $user->id]);
                return false;
            }
            $result = Hash::check($password, $user->getAuthPassword());
            Log::info('Step 5: Web user verification result', ['result' => $result]);
            return $result;
        }
        
        return false;
    }
    
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        return false;
    }
}