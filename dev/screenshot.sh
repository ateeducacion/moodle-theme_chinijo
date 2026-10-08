#!/bin/sh
# Capture the README screenshots from the running local site (make up && make seed first).
# Usage: dev/screenshot.sh SITE_URL   (for example http://localhost:8080)
set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SITE_URL="${1:-http://localhost:8080}"
IMAGE="mcr.microsoft.com/playwright:v1.64.0-noble"
# Moodle builds absolute URLs from its wwwroot (http://localhost:PORT), so the browser runs on the
# host network (Docker Engine on Linux, or Docker Desktop with host networking).

mkdir -p "$ROOT/docs/screenshots" "$ROOT/build/screenshot"
cp "$ROOT/dev/screenshot.mjs" "$ROOT/build/screenshot/screenshot.mjs"
docker run --rm --network host \
    -v "$ROOT/build/screenshot:/work" -v "$ROOT/docs/screenshots:/out" -w /work "$IMAGE" \
    sh -c "npm init -y >/dev/null && npm install --no-audit --no-fund --silent playwright@1.64.0 && node screenshot.mjs '$SITE_URL' /out"
ls -l "$ROOT/docs/screenshots"
