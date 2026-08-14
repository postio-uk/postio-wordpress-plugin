#!/usr/bin/env bash
#
# Build the WP.org distribution zip.
#
#   ./bin/build-zip.sh            # -> dist/postio-address-validation.zip
#
# Two things this exists to get right:
#
#  1. **The folder name.** The git repo is `postio-wordpress`, but the
#     WP.org slug is `postio-address-validation`, and the Text Domain
#     must match the slug. Checking the repo folder directly produces ~50
#     bogus TextDomainMismatch errors plus a "restricted term wordpress
#     in your slug" warning — all artefacts of the local folder name.
#     The zip is the only honest thing to run Plugin Check against.
#
#  2. **What ships.** Excludes everything in .distignore, so no hidden
#     files, shell scripts or stray markdown reach SVN trunk.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="postio-address-validation"
DIST="$ROOT/dist"
STAGE="$DIST/$SLUG"

command -v rsync >/dev/null || { echo "rsync not found" >&2; exit 1; }
command -v zip   >/dev/null || { echo "zip not found" >&2; exit 1; }

VERSION="$(grep -oP "^\s*\*\s*Version:\s*\K[0-9.]+" "$ROOT/postio-address-validation.php" | head -1)"
STABLE="$(grep -oP "^Stable tag:\s*\K[0-9.]+" "$ROOT/readme.txt" | head -1)"

if [[ "$VERSION" != "$STABLE" ]]; then
	echo "!! Version mismatch: plugin header $VERSION, readme Stable tag $STABLE" >&2
	echo "   WP.org serves whatever Stable tag says. Align them before releasing." >&2
	exit 1
fi

rm -rf "$DIST"
mkdir -p "$STAGE"

# --exclude-from wants one pattern per line; .distignore is exactly that.
rsync -a --exclude-from="$ROOT/.distignore" --exclude 'dist' "$ROOT/" "$STAGE/"

# --- reproducible output -------------------------------------------------
#
# Without this, two clean checkouts of the same commit produce zips with
# different checksums, because a zip records each file's mtime and git
# sets those to checkout time. Identical contents, different hash — so
# there'd be no way to confirm which zip was actually uploaded to WP.org.
#
# Pin every mtime to the commit date and feed zip a sorted file list
# (archive order otherwise follows directory-walk order, which the
# filesystem is free to vary). -X drops the extra platform attributes.
SOURCE_DATE_EPOCH="${SOURCE_DATE_EPOCH:-$(git -C "$ROOT" log -1 --format=%ct 2>/dev/null || echo 1700000000)}"
find "$STAGE" -exec touch -d "@$SOURCE_DATE_EPOCH" {} +

( cd "$DIST" && find "$SLUG" -print | LC_ALL=C sort | zip -qX "$SLUG.zip" -@ )

echo "Built $DIST/$SLUG.zip (version $VERSION)"
echo "  sha256 $(sha256sum "$DIST/$SLUG.zip" | cut -d' ' -f1)"
echo
echo "Contents:"
( cd "$STAGE" && find . -type f | sed 's|^\./|  |' | sort )
