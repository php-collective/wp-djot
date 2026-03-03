#!/bin/bash

# Build distribution zip for WordPress.org plugin upload
# Usage: ./scripts/build.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$PLUGIN_DIR/build"
TMP_DIR=$(mktemp -d)

# Read version from plugin header
VERSION=$(grep -oP '^\s*\*\s*Version:\s*\K[0-9]+\.[0-9]+\.[0-9]+' "$PLUGIN_DIR/wp-djot.php")
if [ -z "$VERSION" ]; then
    echo "Error: Could not read version from wp-djot.php"
    rm -rf "$TMP_DIR"
    exit 1
fi

ZIP_NAME="wp-djot-${VERSION}.zip"
echo "Building wp-djot $VERSION..."

# Install production-only dependencies
echo "Installing production dependencies..."
cp "$PLUGIN_DIR/composer.json" "$PLUGIN_DIR/composer.lock" "$TMP_DIR/"
php "$(command -v composer)" install --working-dir="$TMP_DIR" --no-dev --optimize-autoloader --no-interaction --quiet

# Downgrade PHP 8.2 syntax to PHP 8.1 for WordPress.org compatibility
echo "Downgrading PHP 8.2 syntax in vendor..."
cp "$PLUGIN_DIR/rector.php" "$PLUGIN_DIR/rector-bootstrap.php" "$TMP_DIR/"
cd "$TMP_DIR"
php -d auto_prepend_file=rector-bootstrap.php "$PLUGIN_DIR/vendor/bin/rector" process --no-diffs 2>/dev/null || true
rm -f "$TMP_DIR/rector.php" "$TMP_DIR/rector-bootstrap.php"
cd "$PLUGIN_DIR"

rm "$TMP_DIR/composer.json" "$TMP_DIR/composer.lock"

# Copy plugin files, excluding development files
echo "Copying plugin files..."
rsync -a \
    --exclude='/.git' \
    --exclude='/.gitattributes' \
    --exclude='/.gitignore' \
    --exclude='/.github' \
    --exclude='/.idea' \
    --exclude='/.vscode' \
    --exclude='/.phpunit.cache' \
    --exclude='/.editorconfig' \
    --exclude='/.ddev' \
    --exclude='/stubs' \
    --exclude='/tests' \
    --exclude='/build' \
    --exclude='/build.sh' \
    --exclude='/scripts' \
    --exclude='/docs' \
    --exclude='/composer.lock' \
    --exclude='/phpcs.xml' \
    --exclude='/phpstan.neon' \
    --exclude='/phpunit.xml.dist' \
    --exclude='/vendor' \
    --exclude='/.distignore' \
    --exclude='CHANGELOG.md' \
    --exclude='CONTRIBUTING.md' \
    --exclude='README.md' \
    --exclude='/_*' \
    --exclude='*.log' \
    --exclude='*.tmp' \
    --exclude='*.zip' \
    "$PLUGIN_DIR/" "$TMP_DIR/"

# Clean vendor non-permitted files
echo "Cleaning vendor files..."
rm -f "$TMP_DIR/vendor/bin/djot"
rm -rf "$TMP_DIR/vendor/php-collective/djot/bin"
rm -rf "$TMP_DIR/vendor/php-collective/djot/fuzz"
rm -rf "$TMP_DIR/vendor/php-collective/djot/tests"
rm -rf "$TMP_DIR/vendor/php-collective/djot/docs"
rm -f "$TMP_DIR/vendor/php-collective/djot/phpcs.xml"
rm -f "$TMP_DIR/vendor/php-collective/djot/phpstan.neon"
rm -f "$TMP_DIR/vendor/php-collective/djot/composer.json"
rm -f "$TMP_DIR/vendor/php-collective/djot/CONTRIBUTING.md"
rm -f "$TMP_DIR/vendor/php-collective/djot/README.md"

# Clean torchlight/engine
rm -rf "$TMP_DIR/vendor/torchlight/engine/.github"
rm -rf "$TMP_DIR/vendor/torchlight/engine/tests"
rm -f "$TMP_DIR/vendor/torchlight/engine/phpunit.xml"
rm -f "$TMP_DIR/vendor/torchlight/engine/composer.json"
rm -f "$TMP_DIR/vendor/torchlight/engine/README.md"

# Clean phiki/phiki
rm -rf "$TMP_DIR/vendor/phiki/phiki/bin"
rm -f "$TMP_DIR/vendor/phiki/phiki/phpunit.xml"
rm -f "$TMP_DIR/vendor/phiki/phiki/phpstan.neon"
rm -f "$TMP_DIR/vendor/phiki/phiki/composer.json"
rm -f "$TMP_DIR/vendor/phiki/phiki/package.json"
rm -f "$TMP_DIR/vendor/phiki/phiki/package-lock.json"
rm -f "$TMP_DIR/vendor/phiki/phiki/CONTRIBUTING.md"
rm -f "$TMP_DIR/vendor/phiki/phiki/README.md"

# Clean all vendor test directories and non-essential files
find "$TMP_DIR/vendor" -type d -name "tests" -exec rm -rf {} + 2>/dev/null || true
find "$TMP_DIR/vendor" -type d -name ".github" -exec rm -rf {} + 2>/dev/null || true
find "$TMP_DIR/vendor" -type d -name ".art" -exec rm -rf {} + 2>/dev/null || true
find "$TMP_DIR/vendor" -type d -name "docs" -exec rm -rf {} + 2>/dev/null || true
find "$TMP_DIR/vendor" -type f -name "*.md" -delete 2>/dev/null || true
find "$TMP_DIR/vendor" -type f -name "phpunit.xml*" -delete 2>/dev/null || true
find "$TMP_DIR/vendor" -type f -name "phpstan.neon" -delete 2>/dev/null || true
find "$TMP_DIR/vendor" -type f -name "phpcs.xml" -delete 2>/dev/null || true
find "$TMP_DIR/vendor" -depth -type d -name "bin" ! -path "$TMP_DIR/vendor/bin" -exec rm -rf {} + 2>/dev/null || true
rm -rf "$TMP_DIR/vendor/bin"

# Build zip
mkdir -p "$BUILD_DIR"
rm -f "$BUILD_DIR/$ZIP_NAME"
cd "$TMP_DIR"
zip -r "$BUILD_DIR/$ZIP_NAME" . -x '*.DS_Store' --quiet

# Cleanup
rm -rf "$TMP_DIR"

echo ""
echo "Built: build/$ZIP_NAME ($(du -h "$BUILD_DIR/$ZIP_NAME" | cut -f1))"
echo "Files: $(zipinfo -1 "$BUILD_DIR/$ZIP_NAME" | wc -l)"
