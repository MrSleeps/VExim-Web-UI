<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$sourcePath = $root.'/composer.json';
$targetPath = $root.'/composer.dev.json';

$contents = file_get_contents($sourcePath);

if ($contents === false) {
    throw new RuntimeException('Unable to read composer.json.');
}

$config = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

if (! isset($config['require']) || ! is_array($config['require'])) {
    throw new RuntimeException('composer.json does not contain a require section.');
}

$corePackageCount = 0;

foreach (array_keys($config['require']) as $package) {
    if (! str_starts_with($package, 'mrsleeps/vexim-web-core-')) {
        continue;
    }

    $config['require'][$package] = 'dev-main';
    $corePackageCount++;
}

if ($corePackageCount !== 12) {
    throw new RuntimeException("Expected 12 VExim core packages, found {$corePackageCount}.");
}

// Development explicitly opts into branch versions without changing the
// production project's stability policy or dependency constraints.
$config['minimum-stability'] = 'dev';
$config['prefer-stable'] = true;

// Keep the development manifest isolated from any unrelated local merge file.
unset($config['extra']['merge-plugin']);

if (($config['extra'] ?? []) === []) {
    unset($config['extra']);
}

$devPackageCount = count(array_filter(
    $config['require'],
    static fn (string $constraint, string $package): bool => str_starts_with($package, 'mrsleeps/vexim-web-core-') && $constraint === 'dev-main',
    ARRAY_FILTER_USE_BOTH,
));

if ($devPackageCount !== 12) {
    throw new RuntimeException("Expected 12 dev-main core constraints, found {$devPackageCount}.");
}

$json = json_encode(
    $config,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
).PHP_EOL;

if (file_put_contents($targetPath, $json) === false) {
    throw new RuntimeException('Unable to write composer.dev.json.');
}

echo "Created composer.dev.json with {$devPackageCount} core packages on dev-main.".PHP_EOL;
