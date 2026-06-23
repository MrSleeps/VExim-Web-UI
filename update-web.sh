#!/usr/bin/env bash
set -euo pipefail

SCRIPT_URL="https://raw.githubusercontent.com/MrSleeps/VExim-Web-UI/refs/heads/main/update-web.sh"

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
        echo "update.sh updated. Re-running..."
        exec "$0" "$@"
    fi

    rm -f "$tmpfile"
}

update_self "$@"

# Check if composer files have local changes
if git diff --quiet composer.json composer.lock; then
    echo "No local changes to composer files."
else
    echo "⚠️  Local changes detected in composer.json and/or composer.lock"
    echo ""
    echo "What would you like to do?"
    echo "  1) Discard local composer changes and use remote version (recommended)"
    echo "  2) Keep your local composer files and try to merge"
    echo "  3) Stash your composer changes and reapply after update"
    echo "  4) Abort the update"
    echo ""
    read -p "Enter your choice (1-4): " choice

    case $choice in
        1)
            echo "Discarding local composer.json and composer.lock..."
            git checkout -- composer.json composer.lock
            ;;
        2)
            echo "Attempting to merge with your local changes..."
            # Stash to allow pull, then pop after
            git stash push -m "composer files before update" composer.json composer.lock
            ;;
        3)
            echo "Stashing composer changes..."
            git stash push -m "composer files before update" composer.json composer.lock
            echo "Changes stashed. They will remain stashed after the update."
            echo "To reapply: git stash pop"
            ;;
        4)
            echo "Update aborted."
            exit 1
            ;;
        *)
            echo "Invalid choice. Update aborted."
            exit 1
            ;;
    esac
fi

echo "Pulling latest Git Repository"
if ! git pull; then
    echo "❌ Git pull failed! Attempting to recover..."
    # If stash was created, pop it back
    if git stash list | grep -q "composer files before update"; then
        git stash pop
    fi
    exit 1
fi

# If we stashed for option 2, pop the stash now
if git stash list | grep -q "composer files before update"; then
    echo "Reapplying your local composer changes..."
    if ! git stash pop; then
        echo "⚠️  Conflicts detected in composer files!"
        echo "Please resolve conflicts manually, then run:"
        echo "  composer update --no-dev --optimize-autoloader"
        echo "  php artisan migrate --force"
        echo "  php artisan optimize:clear && php artisan optimize"
        exit 1
    fi
fi

echo "Updating core and plugins via Composer..."
composer update --no-dev --optimize-autoloader

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan optimize:clear && php artisan optimize

echo "✅ Update complete."
