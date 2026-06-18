<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class CreateSystemAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:create-sysadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a system administrator with system_admin role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Create System Administrator');
        $this->info('========================================');
        $this->newLine();

        // Check if system_admin role exists
        $systemAdminRole = Role::where('name', 'system_admin')->first();
        if (!$systemAdminRole) {
            $this->error('system_admin role not found! Please run migrations and seeders first.');
            return 1;
        }

        // Get email
        $email = $this->ask('Enter email address');
        
        // Validate email
        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format. Please try again.');
            return 1;
        }
        
        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->warn('A user with this email already exists!');
            
            if ($this->confirm('Do you want to assign system_admin role to this user?', false)) {
                $existingUser->assignRole($systemAdminRole);
                $this->updateAdminFlags($existingUser);
                $this->info('system_admin role assigned to existing user!');
                return 0;
            }
            
            return 1;
        }
        
        // Get password
        $password = $this->secret('Enter password');
        $passwordConfirmation = $this->secret('Confirm password');
        
        // Validate password
        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return 1;
        }
        
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters!');
            return 1;
        }
        
        // Get name (required in your table)
        $name = $this->ask('Enter full name', 'System Administrator');
        
        // Create the user
        $this->info('Creating system administrator...');
        
        try {
            // Base user data for Laravel's default users table
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ];
            
            // Add recovery_email if you want to set it (optional)
            $recoveryEmail = $this->ask('Enter recovery email (optional)', null);
            if ($recoveryEmail) {
                $userData['recovery_email'] = $recoveryEmail;
            }
            
            $user = User::create($userData);
            
            // Assign system_admin role
            $user->assignRole($systemAdminRole);
            
            $this->newLine();
            $this->info('========================================');
            $this->info('System Administrator created successfully!');
            $this->info("Name: {$name}");
            $this->info("Email: {$email}");
            $this->info("Role: system_admin");
            if ($recoveryEmail) {
                $this->info("Recovery Email: {$recoveryEmail}");
            }
            $this->info('========================================');
            $this->newLine();
            
            $this->info('Note: You can generate an app authentication secret later if needed.');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Validate email format
     */
    private function validateEmail($email)
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email'
        ]);
        
        return !$validator->fails();
    }
    
    /**
     * Update admin flags on the user record
     */
    private function updateAdminFlags($user)
    {
        // For the users_web table, there are no specific admin flags
        // The permissions are handled by Spatie's role system
        // So we just need to ensure the user has the role
        
        $this->info('User permissions updated via Spatie role system');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'admin')) {
            $legacyUser = \DB::table('users')->where('email', $user->email)->first();
            if ($legacyUser) {
                \DB::table('users')->where('email', $user->email)->update([
                    'admin' => 1,
                    'type' => 'admin',
                    'enabled' => 1
                ]);
                $this->info('Legacy admin flags updated');
            }
        }
    }
}