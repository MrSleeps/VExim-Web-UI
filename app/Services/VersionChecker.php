<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionChecker
{
    
    protected const INCLUDE_PRERELEASES = false;
    
    /**
     * Get GitHub owner from config
     */
    protected function getGitHubOwner(): string
    {
        return config('vexim.package.org', 'MrSleeps');
    }
    
    /**
     * Get GitHub repo name from config
     */
    protected function getGitHubRepo(): string
    {
        return config('vexim.package.name', 'VExim-Web-UI');
    }
    
    /**
     * Get current version from composer.json
     */
    public function getCurrentVersion(): ?string
    {
        $composerPath = base_path('composer.json');
        
        if (!file_exists($composerPath)) {
            return null;
        }
        
        $composerData = json_decode(file_get_contents($composerPath), true);
        return $composerData['version'] ?? null;
    }
    
    /**
     * Check if update is available
     */
    public function checkForUpdates(): array
    {
        $currentVersion = $this->getCurrentVersion();
        
        if (!$currentVersion) {
            return [
                'success' => false,
                'message' => 'No version defined in composer.json',
                'update_available' => false,
            ];
        }
        
        $latestVersion = $this->fetchLatestVersion();
        
        if (!$latestVersion) {
            return [
                'success' => false,
                'message' => 'Could not fetch latest version from GitHub',
                'update_available' => false,
                'current_version' => $currentVersion,
            ];
        }
        
        $updateAvailable = $this->isNewerVersionAvailable($currentVersion, $latestVersion);
        
        // ONLY calculate priority if an update is available
        $updatePriority = null;
        if ($updateAvailable) {
            $updatePriority = $this->getUpdatePriority($currentVersion, $latestVersion['version']);
        }
        
        return [
            'success' => true,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion['version'],
            'is_prerelease' => $latestVersion['is_prerelease'],
            'update_available' => $updateAvailable,
            'update_priority' => $updatePriority,
            'channel' => $this->getVersionChannel($latestVersion['version']),
            'repository' => $this->getGitHubOwner() . '/' . $this->getGitHubRepo(), // Added for context
        ];
    }
    
    /**
     * Determine update priority level
     */
    public function getUpdatePriority(string $currentVersion, string $latestVersion): string
    {
        $currentIsBeta = $this->isPrerelease($currentVersion);
        $latestIsBeta = $this->isPrerelease($latestVersion);
        
        // Critical: Stable update available while on beta
        if ($currentIsBeta && !$latestIsBeta) {
            return 'high';
        }
        
        // Normal: Both stable or both beta
        if (!$currentIsBeta && !$latestIsBeta) {
            return 'medium';
        }
        
        // Low priority: Newer beta version
        if ($currentIsBeta && $latestIsBeta) {
            return 'low';
        }
        
        // Minor: Latest is beta but you're on stable (probably don't want this)
        if (!$currentIsBeta && $latestIsBeta) {
            return 'ignore';
        }
        
        return 'medium';
    }
    
    /**
     * Check if a version is prerelease (beta, alpha, rc, etc.)
     */
    protected function isPrerelease(string $version): bool
    {
        return preg_match('/-(beta|alpha|rc|dev|pre|test|preview)/i', $version) === 1;
    }
    
    /**
     * Get base version without prerelease suffix
     */
    protected function getBaseVersion(string $version): string
    {
        return preg_replace('/[-+].*$/', '', $version);
    }
    
    /**
     * Get the version channel
     */
    protected function getVersionChannel(string $version): string
    {
        if (str_contains($version, '-beta')) return 'beta';
        if (str_contains($version, '-alpha')) return 'alpha';
        if (str_contains($version, '-rc')) return 'release candidate';
        if (str_contains($version, '-dev')) return 'development';
        return 'stable';
    }
    
    /**
     * Compare versions with prerelease awareness
     */
    protected function isNewerVersionAvailable(string $current, array $latest): bool
    {
        $latestVersion = $latest['version'];
        
        $currentBase = $this->getBaseVersion($current);
        $latestBase = $this->getBaseVersion($latestVersion);
        
        if (version_compare($latestBase, $currentBase, '>')) {
            return true;
        }
        
        if (version_compare($latestBase, $currentBase, '==')) {
            $currentIsPrerelease = $this->isPrerelease($current);
            $latestIsPrerelease = $this->isPrerelease($latestVersion);
            
            if ($currentIsPrerelease && !$latestIsPrerelease) {
                return true;
            }
            
            if ($currentIsPrerelease && $latestIsPrerelease) {
                return $this->comparePrereleaseVersions($current, $latestVersion) > 0;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch the latest version from GitHub using config values
     */
    protected function fetchLatestVersion(): ?array
    {
        $owner = $this->getGitHubOwner();
        $repo = $this->getGitHubRepo();
        $cacheKey = "github_latest_version_{$owner}_{$repo}";
        
        return Cache::remember($cacheKey, 3600, function() use ($owner, $repo) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'VExim-Web-UI/' . $this->getCurrentVersion(),
                ])->get("https://api.github.com/repos/{$owner}/{$repo}/releases");
                
                if (!$response->successful()) {
                    Log::warning('GitHub API request failed', [
                        'owner' => $owner,
                        'repo' => $repo,
                        'status' => $response->status(),
                    ]);
                    return null;
                }
                
                $releases = $response->json();
                
                foreach ($releases as $release) {
                    $tagName = ltrim($release['tag_name'], 'v');
                    $isPrerelease = $release['prerelease'] ?? false;
                    
                    if (self::INCLUDE_PRERELEASES) {
                        return [
                            'version' => $tagName,
                            'is_prerelease' => $isPrerelease,
                        ];
                    }
                    
                    if (!$isPrerelease) {
                        return [
                            'version' => $tagName,
                            'is_prerelease' => false,
                        ];
                    }
                }
                
                if (!empty($releases)) {
                    $tagName = ltrim($releases[0]['tag_name'], 'v');
                    return [
                        'version' => $tagName,
                        'is_prerelease' => $releases[0]['prerelease'] ?? false,
                    ];
                }
                
                return null;
                
            } catch (\Exception $e) {
                Log::warning('GitHub version check failed: ' . $e->getMessage());
                return null;
            }
        });
    }
    
    /**
     * Compare two prerelease versions
     */
    protected function comparePrereleaseVersions(string $version1, string $version2): int
    {
        $pattern = '/-(beta|alpha|rc|dev)\.?(\d+)?/i';
        
        preg_match($pattern, $version1, $matches1);
        preg_match($pattern, $version2, $matches2);
        
        $type1 = $matches1[1] ?? '';
        $type2 = $matches2[1] ?? '';
        $num1 = isset($matches1[2]) ? (int)$matches1[2] : 0;
        $num2 = isset($matches2[2]) ? (int)$matches2[2] : 0;
        
        $typeOrder = ['dev' => 1, 'alpha' => 2, 'beta' => 3, 'rc' => 4];
        
        $order1 = $typeOrder[strtolower($type1)] ?? 0;
        $order2 = $typeOrder[strtolower($type2)] ?? 0;
        
        if ($order1 !== $order2) {
            return $order2 - $order1;
        }
        
        return $num2 - $num1;
    }
}