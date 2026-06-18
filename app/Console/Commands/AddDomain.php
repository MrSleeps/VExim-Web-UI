<?php

namespace App\Console\Commands;

use App\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:add-domain
                            {domain : The domain name (e.g., example.com)}
                            {--maildir= : Path to the mail storage directory}
                            {--uid=5000 : Unix user ID for the domain\'s mail storage}
                            {--gid=5000 : Unix group ID for the domain\'s mail storage}
                            {--max-accounts=100 : Maximum number of email accounts allowed}
                            {--quotas=1024 : Storage quota in megabytes for the domain}
                            {--type=local : Domain type (local, alias, remote)}
                            {--maxmsgsize=10485760 : Maximum allowed message size in bytes}
                            {--sa-tag=2 : SpamAssassin score threshold for tagging}
                            {--sa-refuse=5 : SpamAssassin score threshold for rejecting}
                            {--avscan : Enable virus scanning}
                            {--blocklists : Enable blocklist filtering}
                            {--enabled : Enable the domain (default: true)}
                            {--mailinglists : Enable mailing list functionality}
                            {--pipe : Allow piping to external programs}
                            {--spamassassin : Enable SpamAssassin spam filtering}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new email domain to the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domainName = $this->argument('domain');
        
        // Check if domain already exists
        $existingDomain = Domain::where('domain', $domainName)->first();
        if ($existingDomain) {
            $this->error("Domain '{$domainName}' already exists!");
            return Command::FAILURE;
        }
        
        // Prepare data for validation
        $data = [
            'domain' => $domainName,
            'type' => $this->option('type'),
            'max_accounts' => $this->option('max-accounts'),
            'quotas' => $this->option('quotas'),
            'maxmsgsize' => $this->option('maxmsgsize'),
            'sa_tag' => $this->option('sa-tag'),
            'sa_refuse' => $this->option('sa-refuse'),
        ];
        
        // Validate input
        $validator = Validator::make($data, [
            'domain' => 'required|string|regex:/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/|max:255',
            'type' => ['required', Rule::in(['local', 'alias', 'remote'])],
            'max_accounts' => 'required|integer|min:1|max:10000',
            'quotas' => 'required|integer|min:0|max:1048576', // Max 1TB
            'maxmsgsize' => 'required|integer|min:1024|max:104857600', // 1KB to 100MB
            'sa_tag' => 'required|integer|min:0|max:100',
            'sa_refuse' => 'required|integer|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }
        
        // Set maildir path if not provided
        $maildir = $this->option('maildir');
        if (!$maildir) {
            $maildir = "/var/vmail/{$domainName}";
        }
        
        // Create the domain
        try {
            $domain = Domain::create([
                'domain' => $domainName,
                'maildir' => $maildir,
                'uid' => $this->option('uid'),
                'gid' => $this->option('gid'),
                'max_accounts' => $this->option('max-accounts'),
                'quotas' => $this->option('quotas'),
                'type' => $this->option('type'),
                'avscan' => $this->option('avscan'),
                'blocklists' => $this->option('blocklists'),
                'enabled' => $this->option('enabled') ?? true,
                'mailinglists' => $this->option('mailinglists'),
                'maxmsgsize' => $this->option('maxmsgsize'),
                'pipe' => $this->option('pipe'),
                'spamassassin' => $this->option('spamassassin'),
                'sa_tag' => $this->option('sa-tag'),
                'sa_refuse' => $this->option('sa-refuse'),
            ]);
            
            // Display success message with domain details
            $this->info("✓ Domain '{$domainName}' has been added successfully!");
            $this->newLine();
            $this->table(
                ['Property', 'Value'],
                [
                    ['Domain ID', $domain->domain_id],
                    ['Domain', $domain->domain],
                    ['Type', $domain->type],
                    ['Mail Directory', $domain->maildir],
                    ['UID/GID', "{$domain->uid}/{$domain->gid}"],
                    ['Max Accounts', $domain->max_accounts],
                    ['Quota', $domain->quotas . ' MB'],
                    ['Enabled', $domain->enabled ? 'Yes' : 'No'],
                    ['Virus Scanning', $domain->avscan ? 'Enabled' : 'Disabled'],
                    ['Blocklists', $domain->blocklists ? 'Enabled' : 'Disabled'],
                    ['SpamAssassin', $domain->spamassassin ? 'Enabled' : 'Disabled'],
                    ['Max Message Size', number_format($domain->maxmsgsize) . ' bytes'],
                    ['SA Tag Score', $domain->sa_tag],
                    ['SA Refuse Score', $domain->sa_refuse],
                ]
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to create domain: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}