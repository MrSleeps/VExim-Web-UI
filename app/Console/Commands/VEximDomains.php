<?php

namespace App\Console\Commands;

use App\Repositories\Interfaces\DomainAliasRepositoryInterface;
use App\Repositories\Interfaces\DomainRepositoryInterface;
use App\Repositories\Interfaces\DomainUserRepositoryInterface;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Console\Command;

class VEximDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:domains
                            {action? : Action to perform (add, delete, search, list, show, activate, deactivate, assign, unassign)}
                            {identifier? : Domain ID or name for actions that need it}
                            {--user= : User ID or email for assign/unassign actions}
                            {--role= : Role to assign (domain_admin or viewer)}
                            {--type= : Filter domains by type (local, alias, remote)}
                            {--status= : Filter domains by status (enabled, disabled)}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage VExim domains (add, delete, search, activate, deactivate, assign users)';
    
    /**
     * @var DomainRepositoryInterface
     */
    protected DomainRepositoryInterface $domainRepository;
    
    /**
     * @var DomainAliasRepositoryInterface
     */
    protected DomainAliasRepositoryInterface $domainAliasRepository;
    
    /**
     * @var DomainUserRepositoryInterface
     */
    protected DomainUserRepositoryInterface $domainUserRepository;
    
    /**
     * @var SettingRepositoryInterface
     */
    protected SettingRepositoryInterface $settingRepository;
    
    /**
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;
    
    /**
     * Constructor.
     */
    public function __construct(
        DomainRepositoryInterface $domainRepository,
        DomainAliasRepositoryInterface $domainAliasRepository,
        DomainUserRepositoryInterface $domainUserRepository,
        SettingRepositoryInterface $settingRepository,
        UserRepositoryInterface $userRepository
    ) {
        parent::__construct();
        $this->domainRepository = $domainRepository;
        $this->domainAliasRepository = $domainAliasRepository;
        $this->domainUserRepository = $domainUserRepository;
        $this->settingRepository = $settingRepository;
        $this->userRepository = $userRepository;
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
            'add' => $this->addDomain(),
            'delete', 'del' => $this->deleteDomain(),
            'search' => $this->searchDomains(),
            'list' => $this->listDomains(),
            'show' => $this->showDomain(),
            'activate' => $this->activateDomain(),
            'deactivate', 'deact' => $this->deactivateDomain(),
            'assign' => $this->assignUser(),
            'unassign' => $this->unassignUser(),
            default => $this->handleUnknownAction()
        };
    }
    
    /**
     * Show help information.
     */
    protected function showHelp(): void
    {
        $this->info('VExim Domain Management Tool');
        $this->info('============================');
        $this->info('');
        $this->info('Available commands:');
        $this->info('');
        $this->info('  Add a new domain:');
        $this->info('    php artisan vw:domains add');
        $this->info('');
        $this->info('  Delete a domain:');
        $this->info('    php artisan vw:domains delete 1');
        $this->info('    php artisan vw:domains delete example.com');
        $this->info('');
        $this->info('  Search domains:');
        $this->info('    php artisan vw:domains search example');
        $this->info('    php artisan vw:domains search example --type=local');
        $this->info('    php artisan vw:domains search example --status=enabled');
        $this->info('');
        $this->info('  List all domains:');
        $this->info('    php artisan vw:domains list');
        $this->info('    php artisan vw:domains list --type=local');
        $this->info('    php artisan vw:domains list --status=enabled');
        $this->info('');
        $this->info('  Show domain details:');
        $this->info('    php artisan vw:domains show 1');
        $this->info('    php artisan vw:domains show example.com');
        $this->info('');
        $this->info('  Activate a domain:');
        $this->info('    php artisan vw:domains activate 1');
        $this->info('    php artisan vw:domains activate example.com');
        $this->info('');
        $this->info('  Deactivate a domain:');
        $this->info('    php artisan vw:domains deactivate 1');
        $this->info('    php artisan vw:domains deactivate example.com');
        $this->info('');
        $this->info('  Assign a user to a domain:');
        $this->info('    php artisan vw:domains assign 1 --user=5 --role=domain_admin');
        $this->info('    php artisan vw:domains assign example.com --user=john@example.com --role=viewer');
        $this->info('');
        $this->info('  Unassign a user from a domain:');
        $this->info('    php artisan vw:domains unassign 1 --user=5');
        $this->info('    php artisan vw:domains unassign example.com --user=john@example.com');
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
     * Add a new domain with interactive prompts.
     */
    protected function addDomain(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('Add New Domain');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line('');

        $domainName = $this->ask('Enter domain name (e.g., example.com)');
        if (!$domainName) {
            $this->error('Domain name is required!');
            return Command::FAILURE;
        }

        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domainName)) {
            $this->error('Invalid domain format!');
            return Command::FAILURE;
        }

        if ($this->domainRepository->existsByDomainName($domainName)) {
            $this->error("Domain '{$domainName}' already exists!");
            return Command::FAILURE;
        }

        $typeMap = [
            'local' => 'Local (mail handled locally)',
            'alias' => 'Alias (forward to another domain)',
        ];

        $selectedTypeDisplay = $this->choice(
            'Select domain type',
            array_values($typeMap),
            0
        );
        $type = array_search($selectedTypeDisplay, $typeMap);

        if ($type === 'alias') {
            return $this->addAliasDomain($domainName);
        }

        return $this->addLocalDomain($domainName);
    }
    
    /**
     * Add a local domain.
     */
    protected function addLocalDomain(string $domainName): int
    {
        $mailRoot = $this->settingRepository->get('mail_root', '/var/mail');
        $defaultUid = $this->settingRepository->get('default_uid', 8);
        $defaultGid = $this->settingRepository->get('default_gid', 8);

        $this->info('');
        $this->info('Optional settings (press enter to skip):');

        $maxAccounts = $this->ask('Maximum number of email accounts (leave empty for unlimited)');
        $quotas = $this->ask('Storage quota in MB (leave empty for unlimited)');
        $maxMsgSize = $this->ask('Maximum message size in bytes (leave empty for default)');

        $spamassassin = $this->confirm('Enable SpamAssassin spam filtering?', false);

        $saTag = null;
        $saRefuse = null;
        if ($spamassassin) {
            $saTag = $this->ask('Spam tag score (default: 5)', '5');
            $saRefuse = $this->ask('Spam refuse score (default: 15)', '15');
        }

        $avscan = $this->confirm('Enable virus scanning?', false);
        $blocklists = $this->confirm('Enable blocklist filtering?', false);

        $defaultMaildir = "{$mailRoot}/{$domainName}";
        $maildir = $this->ask('Mail directory path', $defaultMaildir);

        $this->line('');
        $this->info("Using system defaults - UID: {$defaultUid}, GID: {$defaultGid}");

        $this->line('');
        $this->info('Domain Summary:');
        $this->line("  Domain:          {$domainName}");
        $this->line("  Type:            Local");
        $this->line("  Max Accounts:    " . ($maxAccounts ?: 'Unlimited'));
        $this->line("  Quota:           " . ($quotas ? "{$quotas} MB" : 'Unlimited'));
        $this->line("  Max Msg Size:    " . ($maxMsgSize ?: 'Default'));
        $this->line("  Spam Filtering:  " . ($spamassassin ? 'Yes' : 'No'));
        if ($spamassassin) {
            $this->line("    - Tag Score:   {$saTag}");
            $this->line("    - Refuse Score: {$saRefuse}");
        }
        $this->line("  Virus Scanning:  " . ($avscan ? 'Yes' : 'No'));
        $this->line("  Blocklists:      " . ($blocklists ? 'Yes' : 'No'));
        $this->line("  Maildir:         {$maildir}");
        $this->line("  UID/GID:         {$defaultUid}/{$defaultGid}");
        $this->line('');

        if (!$this->confirm('Create this domain?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            $domainData = [
                'domain' => $domainName,
                'type' => 'local',
                'enabled' => true,
                'maildir' => $maildir,
                'uid' => $defaultUid,
                'gid' => $defaultGid,
                'spamassassin' => $spamassassin,
                'avscan' => $avscan,
                'blocklists' => $blocklists,
                'mailinglists' => false,
                'pipe' => false,
            ];

            if ($maxAccounts) {
                $domainData['max_accounts'] = (int)$maxAccounts;
            }

            if ($quotas) {
                $domainData['quotas'] = (int)$quotas;
            }

            if ($maxMsgSize) {
                $domainData['maxmsgsize'] = (int)$maxMsgSize;
            }

            if ($spamassassin) {
                $domainData['sa_tag'] = (int)$saTag;
                $domainData['sa_refuse'] = (int)$saRefuse;
            }

            $domain = $this->domainRepository->create($domainData);

            $this->line('');
            $this->info('Domain created successfully!');
            $this->info("  ID:     {$domain->domain_id}");
            $this->info("  Domain: {$domain->domain}");
            $this->info("  Type:   local");
            $this->info("  Status: Enabled");

            if ($this->confirm('Do you want to assign an administrator to this domain?', false)) {
                $this->assignUserToDomain($domain->domain_id);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create domain: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Add an alias domain.
     */
    protected function addAliasDomain(string $aliasName): int
    {
        $this->info('');
        $this->info('Alias domains forward all email to a primary domain.');
        $this->line('');
        
        $primaryDomainIdentifier = $this->ask('Enter the primary domain ID or name that this alias should forward to');
        
        if (!$primaryDomainIdentifier) {
            $this->error('Primary domain is required!');
            return Command::FAILURE;
        }
        
        $primaryDomain = $this->findDomainByIdentifier($primaryDomainIdentifier);
        
        if (!$primaryDomain) {
            $this->error("Primary domain '{$primaryDomainIdentifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($primaryDomain->type !== 'local') {
            $this->error("Primary domain must be a local domain. '{$primaryDomain->domain}' is type '{$primaryDomain->type}'.");
            return Command::FAILURE;
        }
        
        $this->line('');
        $this->info('Alias Summary:');
        $this->line("  Alias Domain:    {$aliasName}");
        $this->line("  Primary Domain:  {$primaryDomain->domain} (ID: {$primaryDomain->domain_id})");
        $this->line('');
        
        if (!$this->confirm('Create this alias?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $alias = $this->domainAliasRepository->create([
                'alias' => $aliasName,
                'domain_id' => $primaryDomain->domain_id,
            ]);
            
            $this->line('');
            $this->info('Alias created successfully!');
            $this->line("  Alias:        {$alias->alias}");
            $this->line("  Forwards to:  {$primaryDomain->domain}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create alias: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Delete a domain by ID or name.
     */
    protected function deleteDomain(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            $this->info('Usage: php artisan vw:domains delete 1');
            $this->info('       php artisan vw:domains delete example.com');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier);
        
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        $userCount = $this->domainUserRepository->countByDomain($domain->domain_id);
        $aliasCount = $this->domainAliasRepository->getByDomainId($domain->domain_id)->count();
        
        $this->line('');
        $this->warn("You are about to delete:");
        $this->line("  ID:     {$domain->domain_id}");
        $this->line("  Domain: {$domain->domain}");
        $this->line("  Type:   {$domain->type}");
        $this->line("  Users:  {$userCount}");
        $this->line("  Aliases: {$aliasCount}");
        $this->line("");
        
        if ($userCount > 0) {
            $this->warn("Warning: This domain has {$userCount} user(s) assigned!");
            $this->warn("Deleting this domain will remove all user assignments.");
        }
        
        if ($aliasCount > 0) {
            $this->warn("Warning: This domain has {$aliasCount} alias(es) that will also be deleted!");
        }
        
        if (!$this->confirm('Are you sure you want to delete this domain?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $this->domainAliasRepository->deleteByDomainId($domain->domain_id);
            $this->domainRepository->delete($domain->domain_id);
            $this->info("Domain '{$domain->domain}' has been deleted successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to delete domain: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Search for domains and display results in a table.
     */
    protected function searchDomains(): int
    {
        $searchTerm = $this->argument('identifier');
        
        if (!$searchTerm) {
            $this->error('Please provide a search term');
            $this->info('Usage: php artisan vw:domains search example');
            return Command::FAILURE;
        }
        
        $criteria = ['search' => $searchTerm];
        
        if ($this->option('type')) {
            $criteria['type'] = $this->option('type');
        }
        
        if ($this->option('status')) {
            $criteria['enabled'] = $this->option('status') === 'enabled';
        }
        
        $domains = $this->domainRepository->search($criteria, ['administrators']);
        
        if ($domains->isEmpty()) {
            $this->warn("No domains found matching '{$searchTerm}'");
            return Command::SUCCESS;
        }
        
        $this->info("Found " . $domains->count() . " domain(s) matching '{$searchTerm}':");
        $this->line('');
        
        $this->table(
            ['ID', 'Domain', 'Type', 'Status', 'Admins', 'Max Accounts', 'Quota (MB)', 'Created'],
            $domains->map(function ($domain) {
                return [
                    $domain->domain_id,
                    $domain->domain,
                    ucfirst($domain->type),
                    $domain->enabled ? 'Enabled' : 'Disabled',
                    $domain->administrators->count(),
                    $domain->max_accounts ?? '∞',
                    $domain->quotas ?? '∞',
                    $domain->created_at ? $domain->created_at->format('Y-m-d H:i') : 'N/A',
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * List all domains with optional filtering.
     */
    protected function listDomains(): int
    {
        $criteria = [];
        
        if ($this->option('type')) {
            $criteria['type'] = $this->option('type');
        }
        
        if ($this->option('status')) {
            $criteria['enabled'] = $this->option('status') === 'enabled';
        }
        
        $criteria['sort_by'] = 'domain';
        $criteria['sort_order'] = 'asc';
        
        $domains = $this->domainRepository->search($criteria, ['administrators']);
        
        if ($domains->isEmpty()) {
            $this->warn("No domains found");
            
            $filters = [];
            if ($this->option('type')) $filters[] = "type: {$this->option('type')}";
            if ($this->option('status')) $filters[] = "status: {$this->option('status')}";
            
            if (!empty($filters)) {
                $this->info("Filters applied: " . implode(', ', $filters));
            }
            
            return Command::SUCCESS;
        }
        
        $this->info("Total Domains: " . $domains->count());
        if ($this->option('type')) $this->info("Type Filter: {$this->option('type')}");
        if ($this->option('status')) $this->info("Status Filter: {$this->option('status')}");
        $this->line("");
        
        $this->table(
            ['ID', 'Domain', 'Type', 'Status', 'Admins', 'Email Accounts', 'Quota Used', 'Quota Limit'],
            $domains->map(function ($domain) {
                $stats = $this->domainRepository->getDomainStatistics($domain->domain_id);
                return [
                    $domain->domain_id,
                    $domain->domain,
                    ucfirst($domain->type),
                    $domain->enabled ? 'Enabled' : 'Disabled',
                    $stats['administrator_count'],
                    $stats['total_users'] . ($domain->max_accounts ? '/' . $domain->max_accounts : ''),
                    $stats['quota_usage_mb'] . ' MB',
                    $domain->quotas ? $domain->quotas . ' MB' : '∞',
                ];
            })
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * Show detailed information about a specific domain.
     */
    protected function showDomain(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            $this->info('Usage: php artisan vw:domains show 1');
            $this->info('       php artisan vw:domains show example.com');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier, ['administrators', 'eximUsers', 'dkim']);
        
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        $stats = $this->domainRepository->getDomainStatistics($domain->domain_id);
        $aliases = $this->domainAliasRepository->getByDomainId($domain->domain_id);
        
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("Domain Details");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->line("");
        $this->line("ID:               {$domain->domain_id}");
        $this->line("Domain:           {$domain->domain}");
        $this->line("Type:             " . ucfirst($domain->type));
        $this->line("Status:           " . ($domain->enabled ? "Enabled" : "Disabled"));
        
        if ($domain->type === 'local') {
            $this->line("Mail Directory:   {$domain->maildir}");
            $this->line("UID/GID:          {$domain->uid}/{$domain->gid}");
            $this->line("");
            
            $this->info("Limits & Quotas:");
            $this->line("  Max Accounts:    " . ($domain->max_accounts ?: 'Unlimited'));
            $this->line("  Current Users:   {$stats['total_users']}");
            $this->line("  Available Slots: " . ($stats['accounts_available'] ?? 'N/A'));
            $this->line("  Quota Limit:     " . ($domain->quotas ? "{$domain->quotas} MB" : 'Unlimited'));
            $this->line("  Max Msg Size:    " . ($domain->maxmsgsize ? number_format($domain->maxmsgsize) . ' bytes' : 'Default'));
            $this->line("");
            
            $this->info("Security Features:");
            $this->line("  SpamAssassin:    " . ($domain->spamassassin ? "Enabled (Tag: {$domain->sa_tag}, Refuse: {$domain->sa_refuse})" : 'Disabled'));
            $this->line("  Virus Scanning:  " . ($domain->avscan ? 'Enabled' : 'Disabled'));
            $this->line("  Blocklists:      " . ($domain->blocklists ? 'Enabled' : 'Disabled'));
            $this->line("  DKIM:            " . ($domain->hasActiveDkim() ? 'Enabled' : 'Disabled'));
            $this->line("");
        }
        
        if ($aliases->isNotEmpty()) {
            $this->info("Domain Aliases:");
            $this->table(
                ['Alias', 'Created At'],
                $aliases->map(function ($alias) {
                    return [
                        $alias->alias,
                        $alias->created_at ? $alias->created_at->format('Y-m-d H:i') : 'N/A',
                    ];
                })
            );
            $this->line("");
        }
        
        if ($domain->administrators->isNotEmpty()) {
            $this->info("Administrators:");
            $this->table(
                ['User ID', 'Name', 'Email', 'Role'],
                $domain->administrators->map(function ($admin) {
                    return [
                        $admin->id,
                        $admin->name,
                        $admin->email,
                        'Domain Admin'
                    ];
                })
            );
        } else {
            $this->line("Administrators: None");
            $this->line("");
        }
        
        if ($domain->domainAlias) {
            $this->info("Domain Alias:");
            $this->line("  Alias: {$domain->domainAlias->alias}");
            $this->line("");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Activate a domain.
     */
    protected function activateDomain(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            $this->info('Usage: php artisan vw:domains activate 1');
            $this->info('       php artisan vw:domains activate example.com');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier);
        
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($domain->enabled) {
            $this->warn("Domain '{$domain->domain}' is already enabled.");
            return Command::SUCCESS;
        }
        
        try {
            $this->domainRepository->enable($domain->domain_id);
            $this->info("Domain '{$domain->domain}' has been activated successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to activate domain: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Deactivate a domain.
     */
    protected function deactivateDomain(): int
    {
        $identifier = $this->argument('identifier');
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            $this->info('Usage: php artisan vw:domains deactivate 1');
            $this->info('       php artisan vw:domains deactivate example.com');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier);
        
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if (!$domain->enabled) {
            $this->warn("Domain '{$domain->domain}' is already disabled.");
            return Command::SUCCESS;
        }
        
        try {
            $this->domainRepository->disable($domain->domain_id);
            $this->info("Domain '{$domain->domain}' has been deactivated successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to deactivate domain: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Assign a user to a domain.
     */
    protected function assignUser(): int
    {
        $identifier = $this->argument('identifier');
        $userIdentifier = $this->option('user');
        $role = $this->option('role') ?? 'domain_admin';
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            return Command::FAILURE;
        }
        
        if (!$userIdentifier) {
            $this->error('Please provide a user ID or email using --user');
            $this->info('Example: php artisan vw:domains assign 1 --user=5 --role=domain_admin');
            return Command::FAILURE;
        }
        
        if (!in_array($role, ['domain_admin', 'viewer'])) {
            $this->error('Invalid role. Role must be either "domain_admin" or "viewer"');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier);
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($domain->type !== 'local') {
            $this->error("Cannot assign users to alias domains. Only local domains can have user assignments.");
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($userIdentifier);
        if (!$user) {
            $this->error("User '{$userIdentifier}' not found!");
            return Command::FAILURE;
        }
        
        if ($role === 'domain_admin' && !$user->isDomainAdmin()) {
            $this->warn("Warning: User '{$user->email}' does not have the 'domain_admin' role in Spatie permissions.");
            $this->warn("You may want to assign the role first using: php artisan vw:users assign-role {$user->id} domain_admin");
            
            if (!$this->confirm('Continue with assignment anyway?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        if ($this->domainUserRepository->userHasAccess($user->id, $domain->domain_id)) {
            $existingRole = $this->domainUserRepository->getUserRoleForDomain($user->id, $domain->domain_id);
            $this->warn("User '{$user->email}' is already assigned to domain '{$domain->domain}' with role '{$existingRole}'");
            
            if ($this->confirm("Do you want to update the role to '{$role}'?", false)) {
                $this->domainUserRepository->updateOrCreate($user->id, $domain->domain_id, $role);
                $this->info("User role updated successfully!");
            } else {
                $this->info('Operation cancelled.');
            }
            return Command::SUCCESS;
        }
        
        try {
            $this->domainUserRepository->create([
                'user_id' => $user->id,
                'domain_id' => $domain->domain_id,
                'role' => $role
            ]);
            
            $roleDisplay = $role === 'domain_admin' ? 'Administrator' : 'Viewer';
            $this->info("User '{$user->email}' has been assigned as {$roleDisplay} to domain '{$domain->domain}' successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to assign user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Unassign a user from a domain.
     */
    protected function unassignUser(): int
    {
        $identifier = $this->argument('identifier');
        $userIdentifier = $this->option('user');
        
        if (!$identifier) {
            $this->error('Please provide a domain ID or name');
            return Command::FAILURE;
        }
        
        if (!$userIdentifier) {
            $this->error('Please provide a user ID or email using --user');
            $this->info('Example: php artisan vw:domains unassign 1 --user=5');
            return Command::FAILURE;
        }
        
        $domain = $this->findDomainByIdentifier($identifier);
        if (!$domain) {
            $this->error("Domain '{$identifier}' not found!");
            return Command::FAILURE;
        }
        
        $user = $this->findUserByIdentifier($userIdentifier);
        if (!$user) {
            $this->error("User '{$userIdentifier}' not found!");
            return Command::FAILURE;
        }
        
        if (!$this->domainUserRepository->userHasAccess($user->id, $domain->domain_id)) {
            $this->warn("User '{$user->email}' is not assigned to domain '{$domain->domain}'");
            return Command::SUCCESS;
        }
        
        $role = $this->domainUserRepository->getUserRoleForDomain($user->id, $domain->domain_id);
        
        $this->warn("You are about to remove user '{$user->email}' (role: {$role}) from domain '{$domain->domain}'");
        
        if (!$this->confirm('Are you sure?', false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $this->domainUserRepository->deleteByUserAndDomain($user->id, $domain->domain_id);
            $this->info("User '{$user->email}' has been unassigned from domain '{$domain->domain}' successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to unassign user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Helper method to assign a user to a domain interactively.
     */
    protected function assignUserToDomain(int $domainId): void
    {
        $userIdentifier = $this->ask('Enter user ID or email to assign as administrator');
        
        if (!$userIdentifier) {
            $this->info('No user assigned.');
            return;
        }
        
        $user = $this->findUserByIdentifier($userIdentifier);
        
        if (!$user) {
            $this->error("User '{$userIdentifier}' not found!");
            return;
        }
        
        if (!$user->isDomainAdmin()) {
            $this->warn("User '{$user->email}' does not have the 'domain_admin' role.");
            if ($this->confirm('Assign the role and continue?', false)) {
                $user->assignRole('domain_admin');
                $this->info("Role 'domain_admin' assigned to user '{$user->email}'");
            } else {
                $this->info('User not assigned.');
                return;
            }
        }
        
        try {
            $this->domainUserRepository->create([
                'user_id' => $user->id,
                'domain_id' => $domainId,
                'role' => 'domain_admin'
            ]);
            $this->info("User '{$user->email}' assigned as administrator to the domain!");
        } catch (\Exception $e) {
            $this->error("Failed to assign user: " . $e->getMessage());
        }
    }
    
    /**
     * Find domain by ID or name.
     */
    protected function findDomainByIdentifier(string $identifier, array $relations = []): ?\App\Models\Domain
    {
        if (is_numeric($identifier)) {
            return $this->domainRepository->findById((int)$identifier, $relations);
        }
        
        return $this->domainRepository->findByDomainName($identifier, $relations);
    }
    
    /**
     * Find user by ID or email.
     */
    protected function findUserByIdentifier(string $identifier): ?\App\Models\User
    {
        if (is_numeric($identifier)) {
            return $this->userRepository->findById((int)$identifier);
        }
        
        return $this->userRepository->findByEmail($identifier);
    }
}