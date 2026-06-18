<?php

namespace App\Console\Commands;

use App\Services\VersionChecker;
use Illuminate\Console\Command;

class CheckVersion extends Command
{
    protected $signature = 'vw:version-check {--priority= : Filter by priority level (high, medium, low, ignore)}';
    protected $description = 'Check application version and update priority';
    
    public function handle(VersionChecker $checker)
    {
        $result = $checker->checkForUpdates();
        
        if (!$result['success']) {
            $this->error($result['message']);
            return 1;
        }
        
        // Prepare table data
        $tableData = [
            ['Current Version', $result['current_version']],
            ['Latest Version', $result['latest_version']],
            ['Update Available', $result['update_available'] ? 'Yes' : 'No'],
        ];
        
        // Only show priority if an update is available
        if ($result['update_available']) {
            $tableData[] = ['Priority', strtoupper($result['update_priority'])];
        } else {
            $tableData[] = ['Priority', 'N/A (no update available)'];
        }
        
        $this->table(['Property', 'Value'], $tableData);
        
        // Filter by priority if specified (only when updates exist)
        if ($priority = $this->option('priority')) {
            if (!$result['update_available']) {
                $this->info("No updates available at all.");
                return 0;
            }
            
            if ($result['update_priority'] !== $priority) {
                $this->info("No updates with priority '{$priority}' available.");
                return 0;
            }
        }
        
        if ($result['update_available']) {
            $exitCode = match($result['update_priority']) {
                'high' => 2,
                'medium' => 1,
                default => 0,
            };
            
            $this->warn("\n⚠️  Update recommended!");
            return $exitCode;
        }
        
        $this->info("\n✅ Application is up to date!");
        return 0;
    }
}