#!/bin/bash

# Version update script for WP Djot
# Usage: ./scripts/version.sh 1.2.0

set -e

if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    echo "Example: $0 1.2.0"
    exit 1
fi

VERSION="$1"

# Validate version format (semver)
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in semver format (e.g., 1.2.0)"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo "Updating version to $VERSION in all files..."

# wp-djot.php - Plugin header Version
sed -i "s/^\( \* Version:\s*\)[0-9]\+\.[0-9]\+\.[0-9]\+/\1$VERSION/" "$PLUGIN_DIR/wp-djot.php"

# wp-djot.php - WPDJOT_VERSION constant
sed -i "s/define('WPDJOT_VERSION', '[0-9]\+\.[0-9]\+\.[0-9]\+')/define('WPDJOT_VERSION', '$VERSION')/" "$PLUGIN_DIR/wp-djot.php"

# assets/blocks/djot/block.json
sed -i "s/\"version\": \"[0-9]\+\.[0-9]\+\.[0-9]\+\"/\"version\": \"$VERSION\"/" "$PLUGIN_DIR/assets/blocks/djot/block.json"

# assets/blocks/djot/index.asset.php
sed -i "s/'version' => '[0-9]\+\.[0-9]\+\.[0-9]\+'/'version' => '$VERSION'/" "$PLUGIN_DIR/assets/blocks/djot/index.asset.php"

# readme.txt - Stable tag
sed -i "s/^Stable tag: [0-9]\+\.[0-9]\+\.[0-9]\+/Stable tag: $VERSION/" "$PLUGIN_DIR/readme.txt"

echo "Done! Updated version to $VERSION in:"
echo "  - wp-djot.php (header and constant)"
echo "  - assets/blocks/djot/block.json"
echo "  - assets/blocks/djot/index.asset.php"
echo "  - readme.txt (stable tag)"
echo ""
echo "Don't forget to update CHANGELOG.md!"
