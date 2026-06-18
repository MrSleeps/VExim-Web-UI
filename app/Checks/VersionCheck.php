<?php

namespace App\Checks;

use App\Services\VersionChecker;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class VersionCheck extends Check
{
    protected bool $includePrereleases = false;
    protected ?string $repoUrl = null;
    
    public function includePrereleases(bool $include = true): self
    {
        $this->includePrereleases = $include;
        return $this;
    }
    
    public function repoUrl(?string $url): self
    {
        $this->repoUrl = $url;
        return $this;
    }
    
    public function run(): Result
    {
        $result = Result::make();
        
        $versionChecker = app(VersionChecker::class);
        $checkResult = $versionChecker->checkForUpdates();
        
        if (!$checkResult['success']) {
            return $result->failed("Version check failed: {$checkResult['message']}");
        }
        
        // Get repo URL from config or passed value
        $repoUrl = $this->repoUrl ?? config('vexim.package.url', 'https://github.com/your-org/vexim');
        
        // Add metadata to the result
        $result->meta([
            'current_version' => $checkResult['current_version'],
            'latest_version' => $checkResult['latest_version'],
            'update_available' => $checkResult['update_available'],
            'update_priority' => $checkResult['update_priority'],
            'repo_url' => $repoUrl, // Add repo URL to metadata
        ]);
        
        // Use priority to determine health status
        if ($checkResult['update_available']) {
            $message = sprintf(
                "Update available: %s → %s (Priority: %s)",
                $checkResult['current_version'],
                $checkResult['latest_version'],
                strtoupper($checkResult['update_priority'])
            );
            
            // Different status based on priority
            switch ($checkResult['update_priority']) {
                case 'high':
                    return $result->failed($message);
                case 'medium':
                    return $result->warning($message);
                case 'low':
                    return $result->notice($message);
                case 'ignore':
                    return $result->ok("New beta available but not recommended for production");
                default:
                    return $result->warning($message);
            }
        }
        
        return $result->ok("Application is up to date ({$checkResult['current_version']})");
    }
}