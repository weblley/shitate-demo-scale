#!/usr/bin/env bash
#
# Release shitate demo scale to GitHub.
# Usage: bin/release.sh <version>   (e.g. bin/release.sh 0.2.0)
#
# Prerequisite: CHANGELOG.md has a "## [<version>] - YYYY-MM-DD" section.
# Flow: bump versions → commit → tag → build zip → push → GitHub Release.
# Every demo site with the plugin installed then sees the update under Plugins
# (includes/github-updater.php reads the latest Release's shitate-demo-scale.zip).
set -euo pipefail

cd "$(dirname "$0")/.."

VERSION="${1:?Usage: bin/release.sh <version> (e.g. 0.2.0)}"
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "✗ Version must look like 1.2.3"; exit 1; }
TAG="v$VERSION"

[ -z "$(git status --porcelain)" ] || { echo "✗ Working tree is not clean — commit or stash first"; exit 1; }
git rev-parse "$TAG" >/dev/null 2>&1 && { echo "✗ Tag $TAG already exists"; exit 1; }

# Release notes = this version's CHANGELOG section.
NOTES="$(awk -v ver="$VERSION" '
	$0 ~ "^## \\[" ver "\\]" { found = 1; next }
	found && /^## \[/ { exit }
	found { print }
' CHANGELOG.md)"
[ -n "$(echo "$NOTES" | tr -d '[:space:]')" ] || {
	echo "✗ CHANGELOG.md に「## [$VERSION] - YYYY-MM-DD」セクションを書いてから実行してください"
	exit 1
}

echo "→ Bumping version to ${VERSION}"
perl -pi -e "s/^( \* Version:\s+).*/\${1}$VERSION/" shitate-demo-scale.php
# SDS_VERSION is the cache buster for the CSS/JS — keep it in lockstep with the header.
perl -pi -e "s/^define\( 'SDS_VERSION', '.*' \);\$/define( 'SDS_VERSION', '$VERSION' );/" shitate-demo-scale.php
grep -q "define( 'SDS_VERSION', '$VERSION' );" shitate-demo-scale.php || {
	echo "✗ shitate-demo-scale.php の SDS_VERSION を更新できませんでした"
	exit 1
}
grep -q "^ \* Version:\s*$VERSION\$" shitate-demo-scale.php || {
	echo "✗ shitate-demo-scale.php の Version ヘッダを更新できませんでした"
	exit 1
}

git add -A
git commit --allow-empty -m "Release $VERSION"
git tag "$TAG"

echo "→ Building zip…"
bin/build-zip.sh

echo "→ Pushing…"
git push origin HEAD "$TAG"

echo "→ Creating GitHub release…"
gh release create "$TAG" "dist/shitate-demo-scale.zip" --title "shitate demo scale $VERSION" --notes "$NOTES"

echo "✓ Released $VERSION → $(gh release view "$TAG" --json url -q .url)"
