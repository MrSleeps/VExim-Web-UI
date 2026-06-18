<?php

namespace App\Console\Commands;

use App\Services\RspamdConfigService;
use Illuminate\Console\Command;

class GenerateRspamdConfig extends Command
{
    protected $signature = 'vw:generate-rspamd-config {--email= : Generate config for specific email}';
    protected $description = 'Generate Rspamd UCL configuration from user spam settings';
    
    protected RspamdConfigService $rspamdService;
    
    public function __construct(RspamdConfigService $rspamdService)
    {
        parent::__construct();
        $this->rspamdService = $rspamdService;
    }
    
    public function handle()
    {
        if ($email = $this->option('email')) {
            $config = $this->rspamdService->generateSingleUserUCL($email);
            if (!$config) {
                $this->error("No configuration found for: $email");
                return 1;
            }
            $this->line($config);
        } else {
            $config = $this->rspamdService->generateUserSettingsUCL();
            $this->line($config);
            $this->info("\nGenerated configuration for all users");
        }
        
        return 0;
    }
}
