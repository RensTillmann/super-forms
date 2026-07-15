#!/usr/bin/env bash
set -euo pipefail
ROOT="$(git rev-parse --show-toplevel)"
TMPDIR="${TMPDIR:-/tmp}"

load_package_policy() {
  local policy="$ROOT/build/release/package-policy.env"
  local lines=()
  local expected
  [[ -f "$policy" ]] || { echo "package policy is missing: $policy" >&2; exit 2; }
  mapfile -t lines < "$policy"
  (( ${#lines[@]} == 1 )) || { echo "package policy must contain exactly one setting" >&2; exit 2; }
  [[ "${lines[0]}" =~ ^MAX_CUSTOMER_ZIP_BYTES=([1-9][0-9]*)$ ]] || { echo "package policy is malformed" >&2; exit 2; }
  expected="${BASH_REMATCH[1]}"
  unset MAX_CUSTOMER_ZIP_BYTES
  source "$policy"
  [[ "$MAX_CUSTOMER_ZIP_BYTES" == "$expected" ]] || { echo "package policy did not load exactly" >&2; exit 2; }
}
load_package_policy

assert_no_source_map() {
  local package="$1"
  local css="$2"
  unzip -p "$package" super-forms/assets/css/frontend/elements.css > "$css"
  if grep -Fq 'sourceMappingURL=' "$css"; then
    echo 'elements.css map marker present' >&2
    return 1
  fi
}

if [[ "${1:-}" == '--negative-source-map' ]]; then
  A="${2:?package zip required for negative source-map contract}"
  FIXTURE_ROOT="$TMPDIR/source-map-fixture"
  FIXTURE_ZIP="$TMPDIR/source-map-fixture.zip"
  rm -rf "$FIXTURE_ROOT" "$FIXTURE_ZIP"
  mkdir -p "$FIXTURE_ROOT"
  unzip -q "$A" -d "$FIXTURE_ROOT"
  printf '\n/*# sourceMappingURL=contract-negative.map */\n' >> "$FIXTURE_ROOT/super-forms/assets/css/frontend/elements.css"
  (cd "$FIXTURE_ROOT" && zip -q -X -r "$FIXTURE_ZIP" super-forms)
  if assert_no_source_map "$FIXTURE_ZIP" "$TMPDIR/negative-elements.css"; then
    echo 'negative source-map contract unexpectedly accepted' >&2
    exit 1
  fi
  echo 'negative source-map contract rejected marker fixture'
  exit 0
fi

A="${1:?first package zip required}"
B="${2:?second package zip required}"
BYTES="$(stat -c%s "$A" 2>/dev/null || stat -f%z "$A")"
(( BYTES <= MAX_CUSTOMER_ZIP_BYTES )) || { echo "package exceeds $MAX_CUSTOMER_ZIP_BYTES-byte ceiling" >&2; exit 1; }
sha256sum "$A" "$B" | awk '{print $1}' | uniq -c | grep -q '^ *2 ' || { echo 'double-build SHA mismatch' >&2; exit 1; }
unzip -Z1 "$A" | LC_ALL=C sort > "$TMPDIR/package-a.list"
unzip -Z1 "$B" | LC_ALL=C sort > "$TMPDIR/package-b.list"
cmp -s "$TMPDIR/package-a.list" "$TMPDIR/package-b.list" || { echo 'double-build listing mismatch' >&2; exit 1; }
! grep -E '\.(sass|css\.map|po|pot)$|(^|/)(docs|build|test|tests)/' "$TMPDIR/package-a.list" >/dev/null || { echo 'non-runtime source in package' >&2; exit 1; }
! grep -Ei '^super-forms/includes/extensions/pdf-generator/fonts/.+\.(json|woff|woff2|ttf|ttc)$' "$TMPDIR/package-a.list" >/dev/null || { echo 'heavy PDF fonts leaked into package' >&2; exit 1; }
for required in super-forms/super-forms.php super-forms/includes/class-shortcodes.php super-forms/includes/extensions/pdf-generator/super-pdf-gen.min.js; do
  grep -Fxq "$required" "$TMPDIR/package-a.list" || { echo "required runtime missing: $required" >&2; exit 1; }
done
grep -q '\.mo$' "$TMPDIR/package-a.list" || { echo 'compiled translation runtime missing' >&2; exit 1; }
assert_no_source_map "$A" "$TMPDIR/elements.css"
if [[ -f "$ROOT/package.json" && -f "$ROOT/package-lock.json" && -f "$ROOT/src/assets/css/frontend/elements.sass" ]]; then
  DEPS="$TMPDIR/deps"
  mkdir -p "$DEPS"
  cp "$ROOT/package.json" "$ROOT/package-lock.json" "$DEPS/"
  (cd "$DEPS" && npm ci --ignore-scripts --no-audit --no-fund)
  "$DEPS/node_modules/.bin/sass" --no-source-map "$ROOT/src/assets/css/frontend/elements.sass" "$TMPDIR/fresh-elements.css"
  cmp -s "$TMPDIR/fresh-elements.css" <(unzip -p "$A" super-forms/assets/css/frontend/elements.css) || { echo 'packaged CSS differs from fresh Sass' >&2; exit 1; }
else
  echo 'checked-in CSS contract: no Sass source/lockfile on this layout' >&2
fi
