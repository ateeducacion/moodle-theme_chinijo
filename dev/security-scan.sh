#!/bin/sh
# Static security analysis of theme_chinijo (development and CI tool, not part of the theme).
#
# - Semgrep CE (pinned image) with registry rulesets that cover PHP and JavaScript:
#   p/php, p/phpcs-security-audit, p/javascript, p/owasp-top-ten, p/cwe-top-25 and p/secrets.
#   Full results (every severity) go to build/security/semgrep.{json,sarif}; the gate fails on any
#   ERROR-severity finding (high/critical). Mustache templates are not covered by these rulesets.
# - gitleaks (pinned image) over the Git history and the working tree (secrets).
#
# Needs Docker and network access (Semgrep downloads the rulesets). Exits non-zero on a gate failure.
set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SEMGREP_IMAGE="semgrep/semgrep:1.180.0"
GITLEAKS_IMAGE="ghcr.io/gitleaks/gitleaks:v8.30.1"
OUT="$ROOT/build/security"
mkdir -p "$OUT"

RULES="--config p/php --config p/phpcs-security-audit --config p/javascript --config p/owasp-top-ten --config p/cwe-top-25 --config p/secrets"
EXCLUDES="--exclude amd/build --exclude fonts --exclude build --exclude .playwright-mcp --exclude node_modules"

echo "Semgrep $SEMGREP_IMAGE: full report"
# shellcheck disable=SC2086
docker run --rm -v "$ROOT:/src" -w /src "$SEMGREP_IMAGE" semgrep scan --metrics=off --disable-version-check \
    $RULES $EXCLUDES --json-output=build/security/semgrep.json --sarif-output=build/security/semgrep.sarif --text

echo "Semgrep gate: ERROR severity (high/critical) findings fail"
status=0
# shellcheck disable=SC2086
docker run --rm -v "$ROOT:/src:ro" -w /src "$SEMGREP_IMAGE" semgrep scan --metrics=off --disable-version-check \
    $RULES $EXCLUDES --severity ERROR --error --quiet || status=1

echo "gitleaks $GITLEAKS_IMAGE: Git history"
docker run --rm -v "$ROOT:/repo:ro" -v "$OUT:/out" "$GITLEAKS_IMAGE" git /repo --redact --no-banner \
    --config /repo/.gitleaks.toml --report-format json --report-path /out/gitleaks-history.json || status=1

echo "gitleaks: working tree"
docker run --rm -v "$ROOT:/repo:ro" -v "$OUT:/out" "$GITLEAKS_IMAGE" dir /repo --redact --no-banner \
    --config /repo/.gitleaks.toml --report-format json --report-path /out/gitleaks-tree.json || status=1

if [ "$status" -ne 0 ]; then
    echo "Security scan failed: see build/security/." >&2
    exit 1
fi
echo "No high or critical Semgrep findings and no secrets found. Reports in build/security/."
