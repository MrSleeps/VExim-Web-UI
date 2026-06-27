<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class ManagePlugins extends Command
{
    protected $signature = 'vw:plugin
        {action : list|install|remove}
        {package? : Composer package name, e.g. veximweb/plugin-pdns}
        {--fresh : Bypass the manifest cache and fetch a fresh copy}';

    protected $description = 'List, install, or remove VExim Web plugins via composer.local.json';

    protected const MANIFEST_URL = 'https://raw.githubusercontent.com/MrSleeps/vexim-plugin-registry/refs/heads/main/main/plugins.json';

    protected const MANIFEST_CACHE_KEY = 'vexim.plugin-manifest';

    protected const MANIFEST_FALLBACK_PATH = 'app/vexim-plugin-manifest-cache.json';

    protected const COMPOSER_LOCAL_FILE = 'composer.local.json';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->handleList(),
            'install' => $this->handleInstall(),
            'remove' => $this->handleRemove(),
            default => $this->failWith("Unknown action '{$action}'. Expected one of: list, install, remove."),
        };
    }

    protected function handleList(): int
    {
        $manifest = $this->fetchManifest();

        if ($manifest === null) {
            return self::FAILURE;
        }

        $installed = $this->getInstalledPackages();

        $rows = collect($manifest['plugins'])
            ->map(fn (array $plugin) => [
                $plugin['package'],
                $plugin['name'] ?? '',
                $plugin['description'] ?? '',
                in_array($plugin['package'], $installed, true) ? 'Installed' : 'Available',
            ])
            ->all();

        $this->table(['Package', 'Name', 'Description', 'Status'], $rows);

        return self::SUCCESS;
    }

    protected function handleInstall(): int
    {
        $package = $this->argument('package');

        if (! $package) {
            return $this->failWith('You must specify a package to install, e.g. vexim:plugin install vexim/vexim-web-plugin-pdns');
        }

        $manifest = $this->fetchManifest();

        if ($manifest === null) {
            return self::FAILURE;
        }

        $entry = collect($manifest['plugins'])->firstWhere('package', $package);

        if ($entry === null) {
            return $this->failWith("'{$package}' is not in the plugin manifest. Run 'vexim:plugin list' to see available plugins.");
        }

        if (in_array($package, $this->getInstalledPackages(), true)) {
            $this->warn("'{$package}' is already installed. Skipping.");

            return self::SUCCESS;
        }

        $constraint = $entry['constraint'] ?? '*';
        $requireArg = $constraint === '*' ? $package : "{$package}:{$constraint}";

        $this->info("Requiring {$requireArg} in composer.local.json...");

        if (! $this->runComposer(['require', $requireArg, '--no-update'], localFile: true)) {
            return $this->failWith('Failed to add the package to composer.local.json. See output above.');
        }

        $this->cleanupLocalLockFile();

        $this->info('Resolving and installing via composer update (this can take a moment)...');

        if (! $this->runComposer(['update', '--no-interaction'], localFile: false)) {
            $this->warn("composer update failed. '{$package}' was added to composer.local.json but is not installed.");
            $this->warn('Fix the issue above and re-run: composer update');

            return self::FAILURE;
        }

        $this->info("'{$package}' installed successfully.");

        return self::SUCCESS;
    }

    protected function handleRemove(): int
    {
        $package = $this->argument('package');

        if (! $package) {
            return $this->failWith('You must specify a package to remove, e.g. vexim:plugin remove veximweb/plugin-pdns');
        }

        if (! in_array($package, $this->getInstalledPackages(), true)) {
            $this->warn("'{$package}' is not currently installed. Nothing to do.");

            return self::SUCCESS;
        }

        $this->info("Removing {$package} from composer.local.json...");

        if (! $this->runComposer(['remove', $package, '--no-update'], localFile: true)) {
            return $this->failWith('Failed to remove the package from composer.local.json. See output above.');
        }

        $this->cleanupLocalLockFile();

        $this->info('Resolving via composer update (this can take a moment)...');

        if (! $this->runComposer(['update', '--no-interaction'], localFile: false)) {
            $this->warn("composer update failed after removing '{$package}'. Check the output above.");

            return self::FAILURE;
        }

        $this->info("'{$package}' removed successfully.");

        return self::SUCCESS;
    }

    /**
     * Run composer, optionally redirected to composer.local.json via the COMPOSER env var.
     * Streams output live to the console rather than buffering it.
     */
    protected function runComposer(array $args, bool $localFile): bool
    {
        $env = $localFile ? ['COMPOSER' => self::COMPOSER_LOCAL_FILE] : [];

        $result = Process::path(base_path())
            ->timeout(300)
            ->env($env)
            ->run(array_merge(['composer'], $args), function (string $type, string $output) {
                $this->output->write($output);
            });

        return $result->successful();
    }

    /**
     * composer.local.json gets its own lock file (composer.local.lock) when COMPOSER
     * is pointed at it. We don't want that file hanging around — it's not used for
     * anything, and the real composer.lock from the subsequent `composer update`
     * is the one that matters.
     */
    protected function cleanupLocalLockFile(): void
    {
        $lockPath = base_path('composer.local.lock');

        if (File::exists($lockPath)) {
            File::delete($lockPath);
        }
    }

    /**
     * Packages currently present in composer.local.json's require block.
     */
    protected function getInstalledPackages(): array
    {
        $path = base_path(self::COMPOSER_LOCAL_FILE);

        if (! File::exists($path)) {
            return [];
        }

        $contents = json_decode(File::get($path), true);

        if (! is_array($contents) || ! isset($contents['require'])) {
            return [];
        }

        return array_keys($contents['require']);
    }

    /**
     * Fetch the plugin manifest, with caching and an on-disk fallback for when
     * the remote registry is unreachable.
     */
    protected function fetchManifest(): ?array
    {
        if (! $this->option('fresh')) {
            $cached = Cache::get(self::MANIFEST_CACHE_KEY);

            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout(5)->get(self::MANIFEST_URL);

            if ($response->successful()) {
                $manifest = $response->json();

                if (is_array($manifest) && isset($manifest['plugins'])) {
                    Cache::put(self::MANIFEST_CACHE_KEY, $manifest, now()->addHours(6));
                    $this->writeFallbackManifest($manifest);

                    return $manifest;
                }
            }

            $this->warn('Plugin registry returned an unexpected response. Falling back to last known copy.');
        } catch (\Throwable $e) {
            $this->warn("Could not reach the plugin registry: {$e->getMessage()}. Falling back to last known copy.");
        }

        return $this->readFallbackManifest();
    }

    protected function writeFallbackManifest(array $manifest): void
    {
        File::ensureDirectoryExists(dirname(storage_path(self::MANIFEST_FALLBACK_PATH)));
        File::put(storage_path(self::MANIFEST_FALLBACK_PATH), json_encode($manifest, JSON_PRETTY_PRINT));
    }

    protected function readFallbackManifest(): ?array
    {
        $path = storage_path(self::MANIFEST_FALLBACK_PATH);

        if (! File::exists($path)) {
            $this->error('No cached plugin manifest available and the registry could not be reached.');

            return null;
        }

        $manifest = json_decode(File::get($path), true);

        if (! is_array($manifest) || ! isset($manifest['plugins'])) {
            $this->error('Cached plugin manifest is corrupt.');

            return null;
        }

        return $manifest;
    }

    protected function failWith(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}