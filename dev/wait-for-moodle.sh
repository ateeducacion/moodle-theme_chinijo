#!/bin/sh
# Wait until the local Moodle answers on its login page, or fail after a timeout.
# Usage: wait-for-moodle.sh SITE_URL COMPOSE_PROJECT [TIMEOUT_SECONDS]
set -eu

url="$1"
project="$2"
timeout="${3:-900}"
elapsed=0

printf 'Waiting for Moodle at %s ' "$url"
while :; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$url/login/index.php" || true)
    if [ "$code" = "200" ]; then
        echo " ready."
        exit 0
    fi
    if [ "$elapsed" -ge "$timeout" ]; then
        echo " timed out after ${timeout}s (last HTTP status: $code)." >&2
        docker compose -p "$project" logs --tail=40 moodle >&2 || true
        exit 1
    fi
    printf '.'
    sleep 5
    elapsed=$((elapsed + 5))
done
