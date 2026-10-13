#!/bin/sh
# Install or upgrade the bind-mounted theme_chinijo and make it the site theme.
# Runs inside the erseco/alpine-moodle container (Alpine, POSIX sh). Idempotent.
set -eu

MOODLE_ROOT=/var/www/html
CLI="$MOODLE_ROOT/admin/cli"

# Plugins below MATURITY_STABLE need --allow-unstable in non-interactive mode.
php "$CLI/upgrade.php" --non-interactive --allow-unstable

version=$(php "$CLI/cfg.php" --component=theme_chinijo --name=version || true)
if [ -z "$version" ]; then
    echo "theme_chinijo is not installed: is the repository mounted in theme/chinijo (or public/theme/chinijo)?" >&2
    exit 1
fi

php "$CLI/cfg.php" --name=theme --set=chinijo
# Development site only: never send e-mail (the demo accounts use example.com addresses).
php "$CLI/cfg.php" --name=noemailever --set=1
php "$CLI/purge_caches.php"
echo "theme_chinijo $version installed and set as the site theme."
