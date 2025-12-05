#!/bin/bash
#
# Build script for WP Djot WordPress plugin
# Creates a distributable zip file ready for WordPress.org or GitHub releases
#

set -e

PLUGIN_SLUG="wp-djot"
VERSION=$(grep "Version:" wp-djot.php | head -1 | awk '{print $NF}')
BUILD_DIR="build"
DIST_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

echo "Building ${PLUGIN_SLUG} v${VERSION}..."

# Clean previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# Install production dependencies only
echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --quiet

# Copy plugin files
echo "Copying files..."
cp -r assets "${DIST_DIR}/"
cp -r languages "${DIST_DIR}/"
cp -r src "${DIST_DIR}/"
cp -r templates "${DIST_DIR}/"
cp -r vendor "${DIST_DIR}/"
cp wp-djot.php "${DIST_DIR}/"
cp uninstall.php "${DIST_DIR}/"
cp readme.txt "${DIST_DIR}/"
cp composer.json "${DIST_DIR}/"
cp LICENSE "${DIST_DIR}/"

# Create zip
echo "Creating zip archive..."
cd "${BUILD_DIR}"
zip -rq "${PLUGIN_SLUG}-${VERSION}.zip" "${PLUGIN_SLUG}"
cd ..

# Restore dev dependencies
echo "Restoring dev dependencies..."
composer install --quiet

# Cleanup
rm -rf "${DIST_DIR}"

echo ""
echo "Build complete: ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
echo ""
echo "To verify contents: unzip -l ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
