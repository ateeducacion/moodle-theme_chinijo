#!/bin/sh
# Entry point of the development-only test runner (dev/ci/Dockerfile).
#
# The repository is mounted read-only at /plugin. The first run for a Moodle
# branch clones Moodle and installs it with moodle-plugin-ci into the /work
# volume; later runs only copy the current sources into that installation and
# re-initialise the test environments when the plugin version changes.
# Reports are written to /out (build/ in the repository).
#
# Commands: lint, fix, phpunit, coverage, behat, a11y, test, shell, help.
set -eu

COMMAND="${1:-help}"
WORK=/work
PLUGIN_SRC=/plugin
PLUGIN_DIR="$WORK/plugin"
MOODLE_DIR="$WORK/moodle"
DATA_DIR="$WORK/moodledata"
OUT=/out
MARKER="$WORK/.installed-${MOODLE_BRANCH}"

export MOODLE_DIR PLUGIN_DIR
export MOODLE_BEHAT_DEFAULT_BROWSER=chrome
# shellcheck disable=SC1091
. "$NVM_DIR/nvm.sh"

log() {
    printf '\n\033[1m==> %s\033[0m\n' "$*"
}

# Copy the repository into the working copy, without development state.
sync_source() {
    mkdir -p "$PLUGIN_DIR"
    # Leading slashes anchor the patterns to the repository root (amd/build must be kept).
    rsync -a --delete \
        --exclude /.git --exclude node_modules --exclude /build --exclude /.env \
        --exclude /.playwright-mcp --exclude '*.zip' \
        "$PLUGIN_SRC/" "$PLUGIN_DIR/"
}

# The copy of the plugin inside Moodle, which moodle-plugin-ci commands work on.
installed_dir() {
    echo "$(public_dir)/theme/chinijo"
}

public_dir() {
    if [ -d "$MOODLE_DIR/public" ]; then
        echo "$MOODLE_DIR/public"
    else
        echo "$MOODLE_DIR"
    fi
}

reset_database() {
    PGPASSWORD="$DB_PASS" psql -q -h "$DB_HOST" -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS \"$DB_NAME\";"
}

# Install Moodle and the plugin once per branch, then keep the installed copy up to date.
ensure_installed() {
    sync_source
    if [ ! -f "$MARKER" ] || [ ! -d "$MOODLE_DIR" ]; then
        log "Installing Moodle ${MOODLE_BRANCH} with moodle-plugin-ci (first run, this takes a while)"
        rm -rf "$MOODLE_DIR" "$DATA_DIR" "$WORK"/.installed-*
        reset_database
        cd "$WORK"
        moodle-plugin-ci install --no-interaction \
            --plugin "$PLUGIN_DIR" --moodle "$MOODLE_DIR" --data "$DATA_DIR" \
            --branch "$MOODLE_BRANCH" --db-type pgsql --db-host "$DB_HOST" \
            --db-user "$DB_USER" --db-pass "$DB_PASS" --db-name "$DB_NAME"
        touch "$MARKER"
    else
        rsync -a --delete "$PLUGIN_DIR/" "$(installed_dir)/"
    fi
    cd "$WORK"
}

# PHPUnit and Behat must be initialised again when the plugin version or the database changes.
ensure_phpunit() {
    if ! php "$(public_dir)/admin/tool/phpunit/cli/util.php" --diag >/dev/null 2>&1; then
        log "Initialising PHPUnit"
        php "$(public_dir)/admin/tool/phpunit/cli/init.php"
    fi
    # The test configuration lists the plugin's coverage settings; rebuild it cheaply every time.
    php "$(public_dir)/admin/tool/phpunit/cli/util.php" --buildconfig >/dev/null
}

ensure_behat() {
    if ! php "$(public_dir)/admin/tool/behat/cli/util.php" --diag >/dev/null 2>&1; then
        log "Initialising Behat"
        php "$(public_dir)/admin/tool/behat/cli/init.php"
    else
        php "$(public_dir)/admin/tool/behat/cli/util.php" --enable >/dev/null
    fi
}

start_web_server() {
    log "Starting the PHP web server for Behat on ${MOODLE_BEHAT_WWWROOT}"
    php -S 0.0.0.0:8000 -t "$(public_dir)" >/tmp/php-server.log 2>&1 &
    sleep 2
}

run_lint() {
    ensure_installed
    failures=""
    for check in \
        "phplint" \
        "phpcs --max-warnings 0" \
        "phpdoc --max-warnings 0" \
        "phpmd" \
        "validate" \
        "savepoints" \
        "mustache" \
        "grunt --max-lint-warnings 0"; do
        log "moodle-plugin-ci $check"
        # shellcheck disable=SC2086
        if ! moodle-plugin-ci $check "$(installed_dir)"; then
            failures="$failures
  - $check"
        fi
    done
    log "Language packs"
    if ! php "$PLUGIN_DIR/dev/check-lang.php" "$(installed_dir)"; then
        failures="$failures
  - language pack parity"
    fi
    if [ -n "$failures" ]; then
        printf '\nFailed checks:%s\n' "$failures"
        return 1
    fi
    log "All static checks passed"
}

run_fix() {
    ensure_installed
    log "Applying Moodle PHPCBF fixes and rebuilding amd/build with Moodle's Grunt"
    moodle-plugin-ci phpcbf "$(installed_dir)" || true
    root=theme/chinijo
    if [ -d "$MOODLE_DIR/public" ]; then
        root=public/theme/chinijo
    fi
    (cd "$MOODLE_DIR" && npx grunt amd --root="$root")
    mkdir -p "$OUT"
    # Compare the fixed installed copy with the repository, entry by entry so that the repository's
    # own build/ directory (reports) is ignored but amd/build is not; the host applies the patch.
    : > "$OUT/fix.patch"
    for entry in "$(installed_dir)"/* "$(installed_dir)"/.[!.]*; do
        name=$(basename "$entry")
        case "$name" in
            build|.git|node_modules|.playwright-mcp|.env|'.[!.]*') continue ;;
        esac
        (cd "$(installed_dir)/.." && diff -ruN "/plugin/$name" "chinijo/$name" \
            | sed 's#^--- /plugin/#--- a/#; s#^+++ chinijo/#+++ b/#' >> "$OUT/fix.patch") || true
    done
    if [ -s "$OUT/fix.patch" ]; then
        echo "Fixes written to build/fix.patch"
    else
        echo "Nothing to fix."
        rm -f "$OUT/fix.patch"
    fi
}

run_phpunit() {
    ensure_installed
    ensure_phpunit
    log "PHPUnit (theme_chinijo)"
    moodle-plugin-ci phpunit --fail-on-warning "$(installed_dir)"
}

run_coverage() {
    ensure_installed
    ensure_phpunit
    log "PHPUnit with PCOV coverage"
    mkdir -p "$OUT/coverage"
    cd "$MOODLE_DIR"
    php -d pcov.enabled=1 -d pcov.directory="$(public_dir)/theme/chinijo" vendor/bin/phpunit \
        --testsuite theme_chinijo_testsuite \
        --coverage-text --coverage-clover "$OUT/coverage/coverage.xml" --coverage-html "$OUT/coverage/html" \
        --log-junit "$OUT/coverage/junit.xml" | tee "$OUT/coverage/summary.txt"
    # Paths in the report point at the plugin in the repository, not at the runner's copy.
    sed -i "s#$(public_dir)/theme/chinijo/##g" "$OUT/coverage/coverage.xml"
    php "$PLUGIN_DIR/dev/coverage-check.php" "$OUT/coverage/coverage.xml" 80
}

run_behat() {
    tags="$1"
    ensure_installed
    ensure_behat
    start_web_server
    log "Behat: $tags"
    mkdir -p "$OUT/behat"
    status=0
    moodle-plugin-ci behat --profile chrome --tags="$tags" --auto-rerun 1 --dump "$(installed_dir)" || status=$?
    if [ -d "$DATA_DIR/behat_dump" ]; then
        cp -r "$DATA_DIR/behat_dump/." "$OUT/behat/" 2>/dev/null || true
    fi
    return $status
}

case "$COMMAND" in
    lint) run_lint ;;
    fix) run_fix ;;
    phpunit) run_phpunit ;;
    coverage) run_coverage ;;
    behat) run_behat "@theme_chinijo" ;;
    a11y) run_behat "@theme_chinijo&&@accessibility" ;;
    test)
        run_phpunit
        run_behat "@theme_chinijo"
        ;;
    shell)
        ensure_installed
        exec bash
        ;;
    help|*)
        echo "Usage: chinijo-ci lint|fix|phpunit|coverage|behat|a11y|test|shell"
        ;;
esac
