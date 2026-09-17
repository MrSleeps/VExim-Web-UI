#!/usr/bin/env bash
set -euo pipefail

SCRIPT_URL="https://raw.githubusercontent.com/MrSleeps/VExim-Web-UI/refs/heads/main/update-web.sh"
LOCK_BACKUP=""

update_self() {
    echo "Checking for update script changes..."
    local tmpfile
    tmpfile=$(mktemp)

    if ! curl -fsSL "$SCRIPT_URL" -o "$tmpfile"; then
        echo "Could not reach GitHub to check for script updates, continuing with current version."
        rm -f "$tmpfile"
        return
    fi

    if ! cmp -s "$tmpfile" "$0"; then
        cp "$tmpfile" "$0"
        chmod +x "$0"
        rm -f "$tmpfile"
        echo "update-web.sh updated. Re-running..."
        exec "$0" "$@"
    fi

    rm -f "$tmpfile"
}

restore_lock_backup() {
    if [[ -n "$LOCK_BACKUP" && -f "$LOCK_BACKUP" ]]; then
        cp "$LOCK_BACKUP" composer.lock
        rm -f "$LOCK_BACKUP"
        LOCK_BACKUP=""
        echo "Restored the pre-update site composer.lock."
    fi
}

cleanup_lock_backup() {
    if [[ -n "$LOCK_BACKUP" && -f "$LOCK_BACKUP" ]]; then
        rm -f "$LOCK_BACKUP"
        LOCK_BACKUP=""
    fi
}

update_self "$@"

# composer.json is upstream-owned. Site-specific plugins belong in the ignored
# composer.local.json file and must not be added directly to composer.json.
if [[ -n "$(git status --porcelain -- composer.json)" ]]; then
    echo "❌ Local changes detected in composer.json."
    echo "Site-specific dependencies should be kept in composer.local.json."
    echo "Commit, revert, or move these changes before running the updater."
    exit 1
fi

# A deployed installation may have a site-specific composer.lock because the
# plugin manager merges composer.local.json into the root Composer resolution.
# Preserve that lock for recovery, then restore the tracked base lock so the
# repository can fast-forward cleanly.
if [[ -n "$(git status --porcelain -- composer.lock)" ]]; then
    echo "Site-specific composer.lock detected; preserving it during the update."

    if [[ -f composer.lock ]]; then
        LOCK_BACKUP=$(mktemp "${TMPDIR:-/tmp}/vexim-composer-lock.XXXXXX")
        cp composer.lock "$LOCK_BACKUP"
    fi

    git restore --source=HEAD --staged --worktree composer.lock
else
    echo "composer.lock matches the repository base lock."
fi

echo "Pulling latest Git repository..."
if ! git pull --ff-only; then
    echo "❌ Git pull failed."
    restore_lock_backup
    exit 1
fi

echo "Checking Composer dependency resolution..."
if ! composer update \
    --no-dev \
    --optimize-autoloader \
    --with-all-dependencies \
    --dry-run \
    --no-scripts \
    --no-interaction \
    --no-progress; then
    echo "❌ Composer could not resolve the updated core and installed plugin set."
    restore_lock_backup
    echo "No Composer package changes were applied. Fix the dependency conflict and run update-web.sh again."
    exit 1
fi

echo "Updating core and installed plugins via Composer..."
if ! composer update \
    --no-dev \
    --optimize-autoloader \
    --with-all-dependencies \
    --no-interaction \
    --no-progress; then
    echo "❌ Composer update failed after dependency resolution succeeded."
    if [[ -n "$LOCK_BACKUP" && -f "$LOCK_BACKUP" ]]; then
        echo "The pre-update composer.lock has been kept at: $LOCK_BACKUP"
        echo "It has not been restored automatically because vendor may have been partially updated."
    fi
    exit 1
fi

cleanup_lock_backup

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan optimize:clear && php artisan optimize

echo "✅ Update complete."
