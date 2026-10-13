#!/bin/sh
# Build an installable Moodle plugin ZIP of theme_chinijo in build/, then check it.
#
# The archive contains a single top-level "chinijo/" directory with the runtime
# files only (including amd/build). Development tooling, tests, Docker files,
# reports, local settings and any procurement material are excluded. Files are
# taken from Git (tracked files only), so untracked local state can never leak.
set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"

version=$(sed -n "s/^\$plugin->version *= *\([0-9]*\);.*/\1/p" version.php)
release=$(sed -n "s/^\$plugin->release *= *'\([^']*\)';.*/\1/p" version.php)
[ -n "$version" ] && [ -n "$release" ] || { echo "Cannot read version.php" >&2; exit 1; }

STAGE="$ROOT/build/package"
ZIP="$ROOT/build/theme_chinijo-$release-$version.zip"
rm -rf "$STAGE" "$ZIP"
mkdir -p "$STAGE/chinijo"

# Runtime files: files known to Git (tracked, or new and not ignored) except the excluded paths below.
git ls-files --cached --others --exclude-standard \
    | grep -vE '^(\.github/|dev/|docs/|tests/|blueprints/|build/|\.env|\.editorconfig|\.gitignore|\.gitattributes|docker-compose\.yml|Makefile|AGENTS\.md|CONTRIBUTING\.md|SECURITY\.md|1st-prompt\.md|blueprint\.json|.*\.feature$)' \
    | while IFS= read -r file; do
        [ -f "$file" ] || continue
        mkdir -p "$STAGE/chinijo/$(dirname "$file")"
        cp "$file" "$STAGE/chinijo/$file"
    done

# Built AMD modules must be present for every source module.
for src in amd/src/*.js; do
    name=$(basename "$src" .js)
    [ -f "$STAGE/chinijo/amd/build/$name.min.js" ] || { echo "Missing amd/build/$name.min.js (run make fix)" >&2; exit 1; }
done

(cd "$STAGE" && find chinijo -type f | LC_ALL=C sort | zip -q -X "$ZIP" -@)

echo "Checking $ZIP"
listing=$(unzip -Z1 "$ZIP")
echo "$listing" | grep -qx 'chinijo/version.php' || { echo "version.php missing" >&2; exit 1; }
echo "$listing" | grep -qx 'chinijo/config.php' || { echo "config.php missing" >&2; exit 1; }
echo "$listing" | grep -qx 'chinijo/lang/en/theme_chinijo.php' || { echo "English strings missing" >&2; exit 1; }
echo "$listing" | grep -qx 'chinijo/lang/es/theme_chinijo.php' || { echo "Spanish strings missing" >&2; exit 1; }
if echo "$listing" | grep -vq '^chinijo/'; then
    echo "Files outside chinijo/" >&2
    exit 1
fi
if echo "$listing" | grep -Eq '(^|/)(\.env|docker-compose|Makefile|node_modules|coverage|\.git/)|PPT-EDICI|\.feature$|/tests/|/dev/'; then
    echo "Development or confidential files found in the package:" >&2
    echo "$listing" | grep -E '(^|/)(\.env|docker-compose|Makefile|node_modules|coverage|\.git/)|PPT-EDICI|\.feature$|/tests/|/dev/' >&2
    exit 1
fi
grep -q "component = 'theme_chinijo'" "$STAGE/chinijo/version.php" || { echo "Wrong component" >&2; exit 1; }

if command -v sha256sum >/dev/null 2>&1; then
    (cd "$ROOT/build" && sha256sum "$(basename "$ZIP")" > "$(basename "$ZIP").sha256")
else
    (cd "$ROOT/build" && shasum -a 256 "$(basename "$ZIP")" > "$(basename "$ZIP").sha256")
fi
echo "$(echo "$listing" | wc -l | tr -d ' ') files, $(du -h "$ZIP" | cut -f1)"
echo "Package: build/$(basename "$ZIP")"
echo "SHA-256: $(cut -d' ' -f1 "$ZIP.sha256")"
