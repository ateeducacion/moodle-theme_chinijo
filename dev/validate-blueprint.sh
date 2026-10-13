#!/bin/sh
# Validate the Moodle Playground blueprints.
#
# 1. Every blueprint against ATE's official JSON schema (downloaded fresh, never vendored), with ajv-cli.
# 2. The portable blueprint.json with erseco/alpine-moodle's own runner validator, which rejects steps
#    that the Docker runner cannot execute (browser-only steps belong in blueprints/).
# 3. Project rules: the theme is installed from this repository and activated, both as critical steps.
#
# Needs Docker and network access. Exits non-zero on any failure.
set -eu

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SCHEMA_URL="https://ateeducacion.github.io/moodle-playground/assets/blueprints/blueprint-schema.json"
ALPINE_IMAGE="erseco/alpine-moodle:v5.3.0"
NODE_IMAGE="node:22-alpine"
OUT="$ROOT/build/blueprint"
mkdir -p "$OUT"

echo "Downloading the official blueprint schema: $SCHEMA_URL"
curl -fsSL "$SCHEMA_URL" -o "$OUT/blueprint-schema.json"

status=0
for blueprint in "$ROOT/blueprint.json" "$ROOT"/blueprints/*.json; do
    name=${blueprint#"$ROOT"/}
    echo "Schema check: $name"
    if ! docker run --rm -v "$ROOT:/src:ro" -v "$OUT:/schema:ro" "$NODE_IMAGE" \
        npx -y ajv-cli@5.0.0 validate --spec=draft2020 --strict=false --all-errors \
        -s /schema/blueprint-schema.json -d "/src/$name"; then
        status=1
    fi
    echo "Project rules: $name"
    if ! docker run --rm -i "$NODE_IMAGE" node -e '
        const bp = JSON.parse(require("fs").readFileSync(0, "utf8"));
        const steps = bp.steps || [];
        const errors = [];
        const install = steps.find((s) => s.step === "installMoodlePlugin");
        if (!install || !/github\.com\/ateeducacion\/moodle-theme_chinijo\/archive\/refs\/heads\/main\.zip$/.test(install.url)) {
            errors.push("installMoodlePlugin must install this repository (main branch archive)");
        } else if (install.critical !== true) {
            errors.push("installMoodlePlugin must be critical");
        }
        const theme = steps.find((s) => s.step === "setTheme");
        if (!theme || theme.name !== "chinijo" || theme.critical !== true) {
            errors.push("setTheme must activate chinijo as a critical step");
        }
        if (steps.indexOf(theme) < steps.indexOf(install)) {
            errors.push("setTheme must come after installMoodlePlugin");
        }
        if (!bp.preferredVersions || bp.preferredVersions.moodle !== "5.3") {
            errors.push("preferredVersions.moodle must be 5.3");
        }
        if (errors.length) { console.error(errors.join("\n")); process.exit(1); }
        console.log("ok");
    ' < "$blueprint"; then
        status=1
    fi
done

echo "Docker runner check (portable steps only): blueprint.json"
if ! docker run --rm --entrypoint moodle-blueprint -e MOODLE_BLUEPRINT=/b/blueprint.json \
    -v "$ROOT/blueprint.json:/b/blueprint.json:ro" "$ALPINE_IMAGE" validate; then
    status=1
fi

if [ "$status" -ne 0 ]; then
    echo "Blueprint validation failed." >&2
    exit 1
fi
echo "All blueprints are valid."
