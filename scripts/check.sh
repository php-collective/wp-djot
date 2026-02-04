#!/bin/bash

# Plugin check script for WP Djot
# Downloads WP-CLI if needed and runs the plugin checker

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
WP_ROOT="$(dirname "$(dirname "$(dirname "$PLUGIN_DIR")")")"
WP_CLI="$WP_ROOT/wp-cli.phar"

# Check if we're in a WordPress installation
if [ ! -f "$WP_ROOT/wp-config.php" ]; then
    echo "Error: Could not find WordPress installation at $WP_ROOT"
    exit 1
fi

# Download WP-CLI if not present
if [ ! -f "$WP_CLI" ]; then
    echo "Downloading WP-CLI..."
    curl -sS -o "$WP_CLI" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x "$WP_CLI"
    echo "WP-CLI downloaded to $WP_CLI"
fi

# Check if plugin-check is installed
if ! php "$WP_CLI" --path="$WP_ROOT" plugin is-installed plugin-check 2>/dev/null; then
    echo "Installing Plugin Check..."
    php "$WP_CLI" --path="$WP_ROOT" plugin install plugin-check --activate
fi

# Ensure plugin-check is activated
php "$WP_CLI" --path="$WP_ROOT" plugin activate plugin-check 2>/dev/null || true

# Run the plugin check
echo ""
echo "Running plugin check for wp-djot..."
echo "============================================"
php "$WP_CLI" --path="$WP_ROOT" plugin check wp-djot --format=table "$@"
