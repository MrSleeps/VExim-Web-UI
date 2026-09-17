# Core package development

The committed `composer.json` and `composer.lock` are the production dependency definition. Development and beta installs use a generated `composer.dev.json` and a separate `composer.dev.lock`, so unreleased core-package code never changes the production manifest or lock file.

Composer supports an alternate manifest through the `COMPOSER` environment variable. When `COMPOSER=composer.dev.json` is used, its lock file is automatically named `composer.dev.lock`.

## Enable core development packages

Generate the development manifest from the current production `composer.json`:

```bash
php scripts/create-core-dev-composer.php
```

Seed the development lock from the production lock so unrelated dependencies stay pinned, then update only the VExim core packages:

```bash
cp composer.lock composer.dev.lock
COMPOSER=composer.dev.json composer update 'mrsleeps/vexim-web-core-*' --with-dependencies --minimal-changes
```

The generated manifest changes all 12 `mrsleeps/vexim-web-core-*` requirements to `dev-main`. Both generated files are ignored by Git.

For later beta/development deployments, keep using the development manifest:

```bash
COMPOSER=composer.dev.json composer install
```

To refresh the core packages to the latest commits on their `main` branches:

```bash
COMPOSER=composer.dev.json composer update 'mrsleeps/vexim-web-core-*' --with-dependencies --minimal-changes
```

Do not commit `composer.dev.json` or `composer.dev.lock`.

## Return a checkout to production/stable dependencies

Run Composer without the `COMPOSER` override:

```bash
composer install --no-dev --optimize-autoloader
```

This uses the committed `composer.json` and `composer.lock`, replacing any development core packages in `vendor` with the production versions.

The generated development files can be removed at any time:

```bash
rm -f composer.dev.json composer.dev.lock
```

## Production

Production must run Composer normally and must not set `COMPOSER=composer.dev.json`:

```bash
composer install --no-dev --optimize-autoloader
```

The committed lock file therefore remains the source of truth for live deployments.

## Releasing a core package

Develop and test package changes through `dev-main` on beta and in the core-development CI job. Once the change is ready, tag a stable package release, update the main application's stable dependency lock, and deploy normally. This keeps beta able to test unreleased package code while live remains tied to released versions.
