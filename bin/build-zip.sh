#!/usr/bin/env bash
#
# Build a distributable zip of shitate demo scale.
# Stages only the files that ship and zips them with the plugin slug as the
# top-level folder (required for WordPress installs / updates).
#
set -euo pipefail

cd "$(dirname "$0")/.."

SLUG="shitate-demo-scale"
DIST="dist"
STAGE="$DIST/$SLUG"

echo "→ Staging files…"
rm -rf "$STAGE" "$DIST/$SLUG.zip"
mkdir -p "$STAGE"

for item in shitate-demo-scale.php uninstall.php includes assets languages README.md CHANGELOG.md; do
	if [ -e "$item" ]; then
		cp -R "$item" "$STAGE/"
	fi
done

find "$STAGE" -name ".DS_Store" -delete
find "$STAGE" -name "*.po" -delete   # only the compiled .mo ships

echo "→ Zipping…"
( cd "$DIST" && zip -rqX "$SLUG.zip" "$SLUG" )
rm -rf "$STAGE"

echo "✓ Built $DIST/$SLUG.zip"
