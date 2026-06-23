<?php

namespace App\Console\Commands;

use VEximweb\Core\Data\Repositories\Interfaces\DomainRepositoryInterface;
use VEximweb\Core\Data\Repositories\Interfaces\EximUserRepositoryInterface;
use VEximweb\Core\Data\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Console\Command;

class VEximEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:email
                            {action? : Action to perform (add, delete, search, list, show, enable, disable)}
                            {identifier? : Email username or ID for actions that need it}
                            {--domain= : Domain ID or name for filtering}
                            {--type= : Filter by type (local, forward, alias, catchall, fail)}
                            {--status= : Filter by status (enabled, disabled)}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Exim email users (add, delete, search, enable, disable)';
    
    /**
     * @var EximUserRepositoryInterface
     */
    protected EximUserRepositoryInterface $eximUserRepository;
    
    /**
     * @var DomainRepositoryInterface
     */
    protected DomainRepositoryInterface $domainRepository;
    
    /**
     * @var SettingRepositoryInterface
     */
    protected SettingRepositoryInterface $settingRepository;
    
    /**
     * Constructor.
     */
    public function __construct(
        EximUserRepositoryInterface $eximUserRepository,
        DomainRepositoryInterface $domainRepository,
        SettingRepositoryInterface $settingRepository
    ) {
        parent::__construct();
        $this->eximUserRepository = $eximUserRepository;
        $this->domainRepository = $domainRepository;
        $this->settingRepository = $settingRepository;
    }
    
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        if (!$action) {
            $this->showHelp();
            return Command::SUCCESS;
        }
        
        return match($action) {
            'add' => $this->addUser(),
            'delete', 'del' => $this->deleteUser(),
            'search' => $this->searchUsers(),
            'list' => $this->listUsers(),
            'show' => $this->showUser(),
            'enable' => $this->enableUser(),
            'disable' => $this->disableUser(),
            default => $this->handleUnknownAction()
        };
    }
    
    /**
     * Show help information.
     */
    protected function showHelp(): void
    {
        $this->info('VExim Email User Management Tool');
        $this->info('=================================');
        $this->info('');
        $this->info('Available commands:');
        $this->info('');
        $this->info('  Add a new email user:');
        $this->info('    php artisan vw:email add');
        $this->info('');
        $this->info('  Delete an email user:');
        $this->info('    php artisan vw:email delete user@example.com');
        $this->info('    php artisan vw:email delete 1');
        $this->info('');
        $this->info('  Search email users:');
        $this->info('    php artisan vw:email search user@example.com');
        $this->info('    php artisan vw:email search user --domain=example.com');
        $this->info('');
        $this->info('  List email users:');
        $this->info('    php artisan vw:email list');
        $this->info('    php artisan vw:email list --domain=example.com');
        $this->info('    php artisan vw:email list --type=local');
        $this->info('    php artisan vw:email list --status=enabled');
        $this->info('');
        $this->info('  Show email user details:');
        $this->info('    php artisan vw:email show user@example.com');
        $this->info('    php artisan vw:email show 1');
        $this->info('');
        $this->info('  Enable an email user:');
        $this->info('    php artisan vw:email enable user@example.com');
        $this->info('');
        $this->info('  Disable an email user:');
        $this->info('    php artisan vw:email disable user@example.com');
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
     * Add a new email user with interactive prompts.
     */
    protected function addUser(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('Add New Email User');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line('');

        // Select domain
        $domains = $this->domainRepository->all();

        if ($domains->isEmpty()) {
            $this->error('No domains available. Please create a domain first.');
            return Command::FAILURE;
        }

        $domainOptions = $domains->map(function ($domain) {
            return $domain->domain . ' (ID: ' . $domain->domain_id . ')';
        })->toArray();

        $selectedDomain = $this->choice('Select domain', $domainOptions, 0);
        preg_match('/ID: (\d+)/', $selectedDomain, $matches);
        $domainId = $matches[1];
        $domain = $this->domainRepository->findById($domainId);

        // Select account type
        $typeMap = [
            'local' => 'Local (standard mailbox)',
            'alias' => 'Alias (forward to another email address)',
            'catch' => 'Catchall (receives all mail for domain)',
            'fail' => 'Fail (rejects mail with error message)',
        ];

        $selectedTypeDisplay = $this->choice(
            'Select account type',
            array_values($typeMap),
            0
        );
        $type = array_search($selectedTypeDisplay, $typeMap);

        // Get localpart based on type
        $localpart = null;

        if ($type === 'catch') {
            $localpart = '*';
            $this->info('Catchall account will receive all email sent to non-existent addresses.');
        } else {
            $localpart = $this->ask('Enter local part (the part before @)');
            if (!$localpart) {
                $this->error('Local part is required!');
                return Command::FAILURE;
            }

            // Validate localpart format
            if (!preg_match('/^[a-zA-Z0-9._%+-]+$/', $localpart)) {
                $this->error('Invalid local part format! Use only letters, numbers, dots, underscores, percent, plus, or hyphen.');
                return Command::FAILURE;
            }
        }

        $username = $localpart . '@' . $domain->domain;

        // Check if user exists
        if ($this->eximUserRepository->existsByUsername($username)) {
            $this->error("User '{$username}' already exists!");
            return Command::FAILURE;
        }

        // Get type-specific configuration
        $smtp = null;
        $pop = null;
        $crypt = null;
        $realname = null;
        $quota = null;
        $spamassassin = false;
        $avscan = false;
        $blocklist = false;
        $whitelist = false;

        switch ($type) {
            case 'local':
                $mailRoot = $this->settingRepository->get('mail_root', '/var/mail');
                $veximPath = $this->settingRepository->get('vexim_path', 'vexim');

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

                $crypt = password_hash($password, PASSWORD_BCRYPT);

                // Set SMTP and POP paths
                $smtp = "{$mailRoot}/{$veximPath}/{$domain->domain}/{$localpart}/Maildir";
                $pop = "{$mailRoot}/{$veximPath}/{$domain->domain}/{$localpart}";

                // Optional settings for local accounts
                $this->info('');
                $this->info('Optional settings (press enter to skip):');

                $realname = $this->ask('Real name / Display name');
                $quota = $this->ask('Storage quota in MB');
                $spamassassin = $this->confirm('Enable spam filtering?', true);
                $avscan = $this->confirm('Enable virus scanning?', false);
                $blocklist = $this->confirm('Enable blocklist filtering?', true);
                $whitelist = $this->confirm('Enable whitelist filtering?', true);
                break;

            case 'alias':
                $forwardEmail = $this->ask('Enter forwarding email address');
                if (!$forwardEmail) {
                    $this->error('Forwarding email address is required!');
                    return Command::FAILURE;
                }

                if (!filter_var($forwardEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->error('Invalid email format!');
                    return Command::FAILURE;
                }

                // For alias accounts, smtp contains the forwarding address
                // pop is empty, type is 'alias'
                $smtp = $forwardEmail;
                $pop = null;

                // Optional realname for alias
                $this->info('');
                $this->info('Optional settings (press enter to skip):');
                $realname = $this->ask('Real name / Display name');
                break;

            case 'catch':
                $targetEmail = $this->ask('Enter email address to forward catchall mail to');
                if (!$targetEmail) {
                    $this->error('Target email address is required!');
                    return Command::FAILURE;
                }

                if (!filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->error('Invalid email format!');
                    return Command::FAILURE;
                }

                // For catchall accounts, both smtp and pop contain the forwarding address
                $smtp = $targetEmail;
                $pop = $targetEmail;

                // Optional realname for catchall - default to 'CatchAll'
                $this->info('');
                $this->info('Optional settings (press enter to skip):');
                $realnameInput = $this->ask('Real name / Display name', 'CatchAll');
                $realname = $realnameInput ?: 'CatchAll';
                break;

            case 'fail':
                $smtp = ":fail:";
                $pop = null;

                // No optional settings for fail accounts
                break;
        }

        // Confirm
        $this->line('');
        $this->info('User Summary:');
        $this->line("  Username:      {$username}");
        $this->line("  Type:          {$type}");
        $this->line("  Local Part:    {$localpart}");
        if ($realname) $this->line("  Real Name:     {$realname}");
        if ($type === 'local') {
            $this->line("  SMTP Path:     {$smtp}");
            $this->line("  POP Path:      {$pop}");
            if ($quota) $this->line("  Quota:         {$quota} MB");
            $this->line("  Spam Filter:   " . ($spamassassin ? 'Yes' : 'No'));
            $this->line("  Virus Scan:    " . ($avscan ? 'Yes' : 'No'));
            $this->line("  Blocklist:     " . ($blocklist ? 'Yes' : 'No'));
        }
        if ($type === 'alias') {
            $this->line("  Forwards To:   {$smtp}");
        }
        if ($type === 'catch') {
            $this->line("  Forwards To:   {$smtp}");
        }
        if ($type === 'fail') {
            $failMsgDisplay = str_replace(':fail: ', '', $smtp);
            $this->line("  Fail Message:  {$failMsgDisplay}");
        }
        $this->line('');

        if (!$this->confirm('Create this email user?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            $userData = [
                'domain_id' => $domainId,
                'localpart' => $localpart,
                'username' => $username,
                'type' => $type,
                'enabled' => true,
                'smtp' => $smtp,
                'pop' => $pop,
                'uid' => ($type === 'catch') ? 666 : 8,
                'gid' => ($type === 'catch') ? 666 : 8,
                'on_vacation' => 0,
                'on_piped' => 0,
                'admin' => 0,
                'spam_drop' => 0,
                'unseen' => 0,
            ];

            // Only add security features for local accounts
            if ($type === 'local') {
                $userData['on_spamassassin'] = $spamassassin ? 1 : 0;
                $userData['on_avscan'] = $avscan ? 1 : 0;
                $userData['on_blocklist'] = $blocklist ? 1 : 0;
                $userData['on_whitelist'] = $whitelist ? 1 : 0;
            } else {
                // Disable security features for non-local accounts
                $userData['on_spamassassin'] = 0;
                $userData['on_avscan'] = 0;
                $userData['on_blocklist'] = 0;
                $userData['on_whitelist'] = 0;
            }

            if ($crypt) {
                $userData['crypt'] = $crypt;
            }

            if ($realname) {
                $userData['realname'] = $realname;
            }

            if ($quota && $type === 'local') {
                $userData['quota'] = (int)$quota;
            }

            $user = $this->eximUserRepository->create($userData);

            $this->line('');
            $this->info('Email user created successfully!');
            $this->info("  ID:       {$user->user_id}");
            $this->info("  Username: {$user->username}");
            $this->info("  Type:     {$type}");
            $this->info("  Status:   Enabled");

            if ($type === 'alias') {
                $this->info("  Forwards to: {$smtp}");
            }
            if ($type === 'catch') {
                $this->info("  Forwards to: {$smtp}");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create email user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Delete an email user by username or ID.
     */
    protected function deleteUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide an email username or ID');
            $this->info('Usage: php artisan vw:email delete user@example.com');
            $this->info('       php artisan vw:email delete 1');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        $this->line('');
        $this->warn("You are about to delete:");
        $this->line("  ID:       {$user->user_id}");
        $this->line("  Username: {$user->username}");
        $this->line("  Type:     {$user->type}");
        $this->line("  Domain:   " . ($user->domain->domain ?? 'N/A'));
        $this->line("");
        
        if (!$this->confirm('Are you sure you want to delete this email user?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $this->eximUserRepository->delete($user->user_id);
            $this->info("Email user '{$user->username}' has been deleted successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to delete email user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Search for email users.
     */
    protected function searchUsers(): int
    {
        $searchTerm = $this->argument('identifier');
        
        if (!$searchTerm) {
            $this->error('Please provide a search term');
            $this->info('Usage: php artisan vw:email search user@example.com');
            return Command::FAILURE;
        }
        
        $criteria = ['search' => $searchTerm];
        
        if ($this->option('domain')) {
            $criteria['domain_name'] = $this->option('domain');
        }
        
        if ($this->option('type')) {
            $criteria['type'] = $this->option('type');
        }
        
        if ($this->option('status')) {
            $criteria['enabled'] = $this->option('status') === 'enabled';
        }
        
        $users = $this->eximUserRepository->search($criteria, ['domain']);
        
        if ($users->isEmpty()) {
            $this->warn("No email users found matching '{$searchTerm}'");
            return Command::SUCCESS;
        }
        
        $this->info("Found " . $users->count() . " email user(s) matching '{$searchTerm}':");
        $this->line('');
        
        $this->table(
            ['ID', 'Username', 'Type', 'Real Name', 'Status', 'Domain', 'Created'],
            $users->map(function ($user) {
                return [
                    $user->user_id,
                    $user->username,
                    ucfirst($user->type),
                    $user->realname ?: '-',
                    $user->enabled ? 'Enabled' : 'Disabled',
                    $user->domain->domain ?? 'N/A',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i') : 'N/A',
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * List all email users with optional filtering.
     */
    protected function listUsers(): int
    {
        $criteria = [];
        
        if ($this->option('domain')) {
            $domainIdentifier = $this->option('domain');
            if (is_numeric($domainIdentifier)) {
                $criteria['domain_id'] = (int)$domainIdentifier;
            } else {
                $criteria['domain_name'] = $domainIdentifier;
            }
        }
        
        if ($this->option('type')) {
            $criteria['type'] = $this->option('type');
        }
        
        if ($this->option('status')) {
            $criteria['enabled'] = $this->option('status') === 'enabled';
        }
        
        $criteria['sort_by'] = 'username';
        $criteria['sort_order'] = 'asc';
        
        $users = $this->eximUserRepository->search($criteria, ['domain']);
        
        if ($users->isEmpty()) {
            $this->warn("No email users found");
            
            $filters = [];
            if ($this->option('domain')) $filters[] = "domain: {$this->option('domain')}";
            if ($this->option('type')) $filters[] = "type: {$this->option('type')}";
            if ($this->option('status')) $filters[] = "status: {$this->option('status')}";
            
            if (!empty($filters)) {
                $this->info("Filters applied: " . implode(', ', $filters));
            }
            
            return Command::SUCCESS;
        }
        
        $this->info("Total Email Users: " . $users->count());
        if ($this->option('domain')) $this->info("Domain Filter: {$this->option('domain')}");
        if ($this->option('type')) $this->info("Type Filter: {$this->option('type')}");
        if ($this->option('status')) $this->info("Status Filter: {$this->option('status')}");
        $this->line("");
        
        $this->table(
            ['ID', 'Username', 'Type', 'Real Name', 'Status', 'Spam', 'Virus', 'Forward', 'Quota'],
            $users->map(function ($user) {
                return [
                    $user->user_id,
                    $user->username,
                    ucfirst($user->type),
                    $user->realname ?: '-',
                    $user->enabled ? 'Enabled' : 'Disabled',
                    $user->on_spamassassin ? 'Yes' : 'No',
                    $user->on_avscan ? 'Yes' : 'No',
                    $user->on_forward ? 'Yes' : 'No',
                    $user->quota ? $user->quota . ' MB' : '-',
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * Show detailed information about a specific email user.
     */
    protected function showUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide an email username or ID');
            $this->info('Usage: php artisan vw:email show user@example.com');
            $this->info('       php artisan vw:email show 1');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier, ['domain']);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("Email User Details");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->line("");
        $this->line("ID:           {$user->user_id}");
        $this->line("Username:     {$user->username}");
        $this->line("Local Part:   {$user->localpart}");
        $this->line("Domain:       " . ($user->domain->domain ?? 'N/A'));
        $this->line("Type:         " . ucfirst($user->type));
        $this->line("Status:       " . ($user->enabled ? "Enabled" : "Disabled"));
        $this->line("Real Name:    " . ($user->realname ?: 'Not set'));
        $this->line("");
        
        if ($user->type === 'local') {
            $this->info("Mail Configuration:");
            $this->line("  SMTP Path:    {$user->smtp}");
            $this->line("  POP Path:     {$user->pop}");
            $this->line("  UID/GID:      {$user->uid}/{$user->gid}");
            $this->line("  Quota:        " . ($user->quota ? "{$user->quota} MB" : 'Unlimited'));
            $this->line("  Max Msg Size: " . ($user->maxmsgsize ? $user->maxmsgsize . ' bytes' : 'Default'));
            $this->line("");
        }
        
        if (in_array($user->type, ['forward', 'alias', 'catchall']) && $user->forward) {
            $this->info("Forwarding:");
            $this->line("  Forwards To:  {$user->forward}");
            $this->line("");
        }
        
        if ($user->type === 'fail' && $user->smtp) {
            $this->info("Failure Configuration:");
            $this->line("  Fail Message: " . str_replace(':fail: ', '', $user->smtp));
            $this->line("");
        }
        
        $this->info("Security Features:");
        $this->line("  SpamAssassin: " . ($user->on_spamassassin ? "Enabled (Tag: {$user->sa_tag}, Refuse: {$user->sa_refuse})" : 'Disabled'));
        $this->line("  Virus Scan:   " . ($user->on_avscan ? 'Enabled' : 'Disabled'));
        $this->line("  Blocklists:   " . ($user->on_blocklist ? 'Enabled' : 'Disabled'));
        $this->line("  Spam Drop:    " . ($user->spam_drop ? 'Yes' : 'No'));
        $this->line("");
        
        $this->info("Other Features:");
        $this->line("  Forwarding:   " . ($user->on_forward ? 'Yes' : 'No'));
        $this->line("  Vacation:     " . ($user->on_vacation ? 'Yes' : 'No'));
        $this->line("  Pipe:         " . ($user->on_piped ? 'Yes' : 'No'));
        $this->line("  Admin:        " . ($user->admin ? 'Yes' : 'No'));
        $this->line("");
        
        $this->info("Timestamps:");
        $this->line("  Created:      " . ($user->created_at ? $user->created_at->format('Y-m-d H:i:s') : 'N/A'));
        $this->line("  Updated:      " . ($user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : 'N/A'));
        $this->line("");
        
        return Command::SUCCESS;
    }
    
    /**
     * Enable an email user.
     */
    protected function enableUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide an email username or ID');
            $this->info('Usage: php artisan vw:email enable user@example.com');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($user->enabled) {
            $this->warn("Email user '{$user->username}' is already enabled.");
            return Command::SUCCESS;
        }
        
        try {
            $this->eximUserRepository->enable($user->user_id);
            $this->info("Email user '{$user->username}' has been enabled successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to enable email user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Disable an email user.
     */
    protected function disableUser(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide an email username or ID');
            $this->info('Usage: php artisan vw:email disable user@example.com');
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($identifier);
        
        if (!$user) {
            $this->error("User '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if (!$user->enabled) {
            $this->warn("Email user '{$user->username}' is already disabled.");
            return Command::SUCCESS;
        }
        
        try {
            $this->eximUserRepository->disable($user->user_id);
            $this->info("Email user '{$user->username}' has been disabled successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to disable email user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Find user by username or ID.
     */
    protected function findUserByIdentifier(string $identifier, array $relations = []): ?\VEximweb\Core\Data\Models\EximUser
    {
        if (is_numeric($identifier)) {
            return $this->eximUserRepository->findById((int)$identifier, $relations);
        }
        
        return $this->eximUserRepository->findByUsername($identifier, $relations);
    }
}