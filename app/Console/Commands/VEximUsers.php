<?php

namespace App\Console\Commands;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class VEximUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:users
                            {action? : Action to perform (add, delete, search, list, show, activate, deactivate)}
                            {search-term? : Search term when using search action}
                            {identifier? : User ID or email for delete/show/activate/deactivate actions}
                            {--role= : Filter users by role (system_admin, domain_admin, domain_user)}
                            {--status= : Filter users by status (active, inactive)}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage your VExim web users';
    
    /**
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;
    
    /**
     * Constructor.
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }
    
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        // If no action specified, show help
        if (!$action) {
            $this->showHelp();
            return Command::SUCCESS;
        }
        
        // Route to the appropriate method
        return match($action) {
            'add' => $this->addUser(),
            'delete', 'del' => $this->deleteUser(),
            'search' => $this->searchUsers(),
            'list' => $this->listUsers(),
            'show' => $this->showUser(),
            'activate' => $this->activateUser(),
            'deactivate', 'deact' => $this->deactivateUser(),
            default => $this->handleUnknownAction()
        };
    }
    
    /**
     * Show help information.
     */
    protected function showHelp(): void
    {
        $this->info('VExim User Management Tool');
        $this->info('=========================');
        $this->info('');
        $this->info('Available commands:');
        $this->info('');
        $this->info('  Add a new user:');
        $this->info('    php artisan vw:users add');
        $this->info('');
        $this->info('  Delete a user:');
        $this->info('    php artisan vw:users delete 1');
        $this->info('    php artisan vw:users delete john@example.com');
        $this->info('');
        $this->info('  Search users:');
        $this->info('    php artisan vw:users search john');
        $this->info('    php artisan vw:users search "john doe"');
        $this->info('    php artisan vw:users search john --role=system_admin');
        $this->info('    php artisan vw:users search john --status=active');
        $this->info('');
        $this->info('  List all users:');
        $this->info('    php artisan vw:users list');
        $this->info('    php artisan vw:users list --role=system_admin');
        $this->info('    php artisan vw:users list --status=active');
        $this->info('');
        $this->info('  Show user details:');
        $this->info('    php artisan vw:users show 1');
        $this->info('    php artisan vw:users show john@example.com');
        $this->info('');
        $this->info('  Activate a user:');
        $this->info('    php artisan vw:users activate 1');
        $this->info('    php artisan vw:users activate john@example.com');
        $this->info('');
        $this->info('  Deactivate a user:');
        $this->info('    php artisan vw:users deactivate 1');
        $this->info('    php artisan vw:users deactivate john@example.com');
        $this->info('');
    }
    
    /**
     * Handle unknown action.
     */
    protected function handleUnknownAction(): int
    {
        $this->error("Unknown action: {$this->argument('action')}");
        $this->info('');
        $this->showHelp();
        return Command::FAILURE;
    }
    
    /**
     * Add a new user with interactive prompts.
     */
    protected function addUser(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('Add New User');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line('');

        // Get name
        $name = $this->ask('Enter full name');
        if (!$name) {
            $this->error('Name is required!');
            return Command::FAILURE;
        }

        // Get email
        $email = $this->ask('Enter email address');
        if (!$email) {
            $this->error('Email is required!');
            return Command::FAILURE;
        }

        // Check if email exists
        if ($this->userRepository->existsByEmail($email)) {
            $this->error("User with email '{$email}' already exists!");
            return Command::FAILURE;
        }

        // Get recovery email (optional)
        $recoveryEmail = $this->ask('Enter recovery email address (optional - press enter to skip)');

        // Validate recovery email if provided
        if ($recoveryEmail && !filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid recovery email format!');
            return Command::FAILURE;
        }

        // Get password with confirmation
        $password = $this->secret('Enter password');
        if (!$password) {
            $this->error('Password is required!');
            return Command::FAILURE;
        }

        $passwordConfirmation = $this->secret('Confirm password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return Command::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters!');
            return Command::FAILURE;
        }

        // Get role with nice display names
        $roleMap = [
            'system_admin' => 'System Admin',
            'domain_admin' => 'Domain Admin',
        ];

        $selectedRoleDisplay = $this->choice(
            'Select role',
            array_values($roleMap),
            0
        );

        // Convert back to database value
        $role = array_search($selectedRoleDisplay, $roleMap);

        // Confirm
        $this->line('');
        $this->info('User Summary:');
        $this->line("  Name:           {$name}");
        $this->line("  Email:          {$email}");
        $this->line("  Recovery Email: " . ($recoveryEmail ?: 'Not provided'));
        $this->line("  Role:           {$selectedRoleDisplay}");
        $this->line('');

        if (!$this->confirm('Create this user?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            // Prepare user data
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'active' => true,
                'email_verified_at' => now(), // Set email as verified for CLI-created users
            ];

            // Add recovery email if provided
            if ($recoveryEmail) {
                $userData['recovery_email'] = $recoveryEmail;
            }

            $user = $this->userRepository->create($userData);

            $this->line('');
            $this->info('User created successfully!');
            $this->info("  ID:             {$user->id}");
            $this->info("  Name:           {$user->name}");
            $this->info("  Email:          {$user->email}");
            $this->info("  Recovery Email: " . ($recoveryEmail ?: 'Not provided'));
            $this->info("  Role:           {$selectedRoleDisplay}");
            $this->info("  Status:         Active");
            $this->info("  Email Verified: Yes (auto-verified for CLI creation)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Delete a user by ID or email.
     */
    protected function deleteUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a user ID or email');
            $this->info('Usage: php artisan vw:users delete 1');
            $this->info('       php artisan vw:users delete john@example.com');
            return Command::FAILURE;
        }
        
        // Find user by ID or email
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        // Show user info
        $this->line('');
        $this->warn("You are about to delete:");
        $this->line("  ID:    {$user->id}");
        $this->line("  Name:  {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("");
        
        // Prevent deleting the last system admin
        if ($user->isSystemAdmin()) {
            $systemAdmins = $this->userRepository->getSystemAdmins();
            if ($systemAdmins->count() <= 1) {
                $this->error('Cannot delete the last system administrator!');
                return Command::FAILURE;
            }
        }
        
        if (!$this->confirm('Are you sure you want to delete this user?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $this->userRepository->delete($user->id);
            $this->info("User '{$user->name}' ({$user->email}) has been deleted successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to delete user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Search for users and display results in a table.
     */
    protected function searchUsers(): int
    {
        $searchTerm = $this->argument('search-term');
        
        if (!$searchTerm) {
            $this->error('Please provide a search term');
            $this->info('Usage: php artisan vw:users search john');
            $this->info('       php artisan vw:users search "john doe"');
            return Command::FAILURE;
        }
        
        $criteria = ['search' => $searchTerm];
        
        // Add role filter if provided
        if ($this->option('role')) {
            $criteria['role'] = $this->option('role');
        }
        
        // Add status filter if provided
        if ($this->option('status')) {
            $criteria['active'] = $this->option('status');
        }
        
        $users = $this->userRepository->search($criteria, ['domains']);
        
        if ($users->isEmpty()) {
            $this->warn("No users found matching '{$searchTerm}'");
            return Command::SUCCESS;
        }
        
        $this->info("Found " . $users->count() . " user(s) matching '{$searchTerm}':");
        $this->line('');
        
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Status', 'Domains', 'Created'],
            $users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $this->getRoleName($user),
                    $user->active ? 'Active' : 'Inactive',
                    $user->domains->count(),
                    $user->created_at->format('Y-m-d H:i')
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * List all users with optional filtering.
     */
    protected function listUsers(): int
    {
        $criteria = [];
        
        // Add role filter if provided
        if ($this->option('role')) {
            $criteria['role'] = $this->option('role');
        }
        
        // Add status filter if provided
        if ($this->option('status')) {
            $criteria['active'] = $this->option('status');
        }
        
        // Sort by ID
        $criteria['sort_by'] = 'id';
        $criteria['sort_order'] = 'asc';
        
        $users = $this->userRepository->search($criteria, ['domains']);
        
        if ($users->isEmpty()) {
            $this->warn("No users found");
            
            // Show filter info
            $filters = [];
            if ($this->option('role')) $filters[] = "role: {$this->option('role')}";
            if ($this->option('status')) $filters[] = "status: {$this->option('status')}";
            
            if (!empty($filters)) {
                $this->info("Filters applied: " . implode(', ', $filters));
            }
            
            return Command::SUCCESS;
        }
        
        // Display summary
        $this->info("Total Users: " . $users->count());
        if ($this->option('role')) $this->info("Role Filter: {$this->option('role')}");
        if ($this->option('status')) $this->info("Status Filter: {$this->option('status')}");
        $this->line("");
        
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Status', 'Domains', 'Last Login', 'Created'],
            $users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $this->getRoleName($user),
                    $user->active ? 'Active' : 'Inactive',
                    $user->domains->count(),
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never',
                    $user->created_at->format('Y-m-d H:i')
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * Show detailed information about a specific user.
     */
    protected function showUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a user ID or email');
            $this->info('Usage: php artisan vw:users show 1');
            $this->info('       php artisan vw:users show john@example.com');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        // Display user details
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("User Details");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->line("");
        $this->line("ID:             {$user->id}");
        $this->line("Name:           {$user->name}");
        $this->line("Email:          {$user->email}");
        $this->line("Role:           {$this->getRoleName($user)}");
        $this->line("Status:         " . ($user->active ? "Active" : "Inactive"));
        $this->line("Email Verified: " . ($user->email_verified_at ? "Yes ({$user->email_verified_at->format('Y-m-d H:i')})" : "No"));
        $this->line("Created:        {$user->created_at->format('Y-m-d H:i:s')}");
        $this->line("Last Login:     " . ($user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : "Never"));
        $this->line("");
        
        // Domain assignments
        if ($user->domains && $user->domains->isNotEmpty()) {
            $this->info("Assigned Domains:");
            $this->table(
                ['Domain ID', 'Domain Name', 'Role'],
                $user->domains->map(function ($domain) {
                    return [
                        $domain->id,
                        $domain->name ?? 'N/A',
                        $domain->pivot->role ?? 'viewer'
                    ];
                })
            );
        } else {
            $this->line("Assigned Domains: None");
        }
        
        $this->line("");
        
        return Command::SUCCESS;
    }
    
    /**
     * Activate a user.
     */
    protected function activateUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a user ID or email');
            $this->info('Usage: php artisan vw:users activate 1');
            $this->info('       php artisan vw:users activate john@example.com');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($user->active) {
            $this->warn("User '{$user->name}' ({$user->email}) is already active.");
            return Command::SUCCESS;
        }
        
        try {
            $this->userRepository->activate($user->id);
            $this->info("User '{$user->name}' ({$user->email}) has been activated successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to activate user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Deactivate a user.
     */
    protected function deactivateUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a user ID or email');
            $this->info('Usage: php artisan vw:users deactivate 1');
            $this->info('       php artisan vw:users deactivate john@example.com');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if (!$user->active) {
            $this->warn("User '{$user->name}' ({$user->email}) is already inactive.");
            return Command::SUCCESS;
        }
        
        // Prevent deactivating the last system admin
        if ($user->isSystemAdmin()) {
            $activeSystemAdmins = $this->userRepository->getSystemAdmins()->filter(function ($admin) {
                return $admin->active;
            })->count();
            
            if ($activeSystemAdmins <= 1) {
                $this->error('Cannot deactivate the last active system administrator!');
                return Command::FAILURE;
            }
        }
        
        try {
            $this->userRepository->deactivate($user->id);
            $this->info("User '{$user->name}' ({$user->email}) has been deactivated successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to deactivate user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Find user by ID or email.
     */
    protected function findUserByIdentifier(string $identifier): ?\App\Models\User
    {
        // Check if identifier is numeric (ID)
        if (is_numeric($identifier)) {
            return $this->userRepository->findById((int)$identifier);
        }
        
        // Otherwise treat as email
        return $this->userRepository->findByEmail($identifier);
    }
    
    /**
     * Get the role name for display.
     */
    protected function getRoleName($user): string
    {
        if ($user->isSystemAdmin()) {
            return 'System Admin';
        }
        if ($user->isDomainAdmin()) {
            return 'Domain Admin';
        }
        if ($user->isDomainUser()) {
            return 'Domain User';
        }
        return 'Unknown';
    }
}