#!/usr/bin/env bash
set -euo pipefail

# Canonical customer package builder. Run from a clean detached checkout in the
# pinned release-build image; never mutate tracked source or build directories.
EXPECTED_NODE="v24.13.1"
EXPECTED_NPM="11.8.0"
EXPECTED_GIT="2.43.0"
ROOT_DIR="$(git rev-parse --show-toplevel)"

load_package_policy() {
  local policy="$ROOT_DIR/build/release/package-policy.env"
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
OUTPUT="${1:-dist/super-forms-release.zip}"
if [[ "$OUTPUT" != /* ]]; then OUTPUT="$ROOT_DIR/$OUTPUT"; fi
REF="${2:-HEAD}"

command -v npm >/dev/null
command -v zip >/dev/null
[[ "$(node --version)" == "$EXPECTED_NODE" ]] || { echo "node version must be $EXPECTED_NODE" >&2; exit 2; }
[[ "$(npm --version)" == "$EXPECTED_NPM" ]] || { echo "npm version must be $EXPECTED_NPM" >&2; exit 2; }
[[ "$(git --version | awk '{print $3}')" == "$EXPECTED_GIT" ]] || { echo "git version must be $EXPECTED_GIT" >&2; exit 2; }
[[ -z "$(git status --porcelain --untracked-files=all)" ]] || { echo "release checkout is dirty" >&2; exit 2; }

COMMIT="$(git rev-parse "${REF}^{commit}")"
[[ "$COMMIT" == "$(git rev-parse HEAD)" ]] || { echo "builder must run at requested commit" >&2; exit 2; }
SOURCE_AUTHOR_DATE="$(git show -s --format=%aI "$COMMIT")"
SOURCE_COMMIT_DATE="$(git show -s --format=%cI "$COMMIT")"
TMP="$(mktemp -d)"
INDEX="$TMP/index"
trap 'rm -rf "$TMP"' EXIT
STAGE="$TMP/stage"
mkdir -p "$STAGE"

git archive --format=tar --prefix=super-forms/ --mtime="$SOURCE_COMMIT_DATE" "$COMMIT:src" | tar -xf - -C "$STAGE"

if [[ -f "$ROOT_DIR/package.json" && -f "$ROOT_DIR/package-lock.json" && -f "$ROOT_DIR/src/assets/css/frontend/elements.sass" ]]; then
  DEPS="$TMP/deps"
  mkdir -p "$DEPS"
  cp "$ROOT_DIR/package.json" "$ROOT_DIR/package-lock.json" "$DEPS/"
  (cd "$DEPS" && npm ci --ignore-scripts --no-audit --no-fund)
  "$DEPS/node_modules/.bin/sass" --no-source-map "$ROOT_DIR/src/assets/css/frontend/elements.sass" "$STAGE/super-forms/assets/css/frontend/elements.css"
  sed -i '/sourceMappingURL=elements\.css\.map/d' "$STAGE/super-forms/assets/css/frontend/elements.css"
fi

# Retain PDF fonts: the runtime fallback needs a writable cache and has not
# been proven against a no-font archive. Remove only non-runtime sources.
rm -rf "$STAGE/super-forms/docs" "$STAGE/super-forms/react" "$STAGE/super-forms/.github"
find "$STAGE/super-forms" -type f \( -name '*.sass' -o -name '*.css.map' -o -name '*.po' -o -name '*.pot' -o -name '*.backup-*' \) -delete
find "$STAGE/super-forms" -type d \( -name build -o -name test -o -name tests -o -name .sass-cache \) -prune -exec rm -rf {} +
rm -f "$STAGE/super-forms/.gitattributes" "$STAGE/super-forms/build-release-zip.sh" "$STAGE/super-forms/zip.sh"

PACKAGE_GIT_DIR="$TMP/package.git"
git init -q --bare "$PACKAGE_GIT_DIR"
GIT_INDEX_FILE="$INDEX" GIT_WORK_TREE="$STAGE" git --git-dir="$PACKAGE_GIT_DIR" read-tree --empty
GIT_INDEX_FILE="$INDEX" GIT_WORK_TREE="$STAGE" git --git-dir="$PACKAGE_GIT_DIR" add --all -- super-forms
TREE="$(GIT_INDEX_FILE="$INDEX" git --git-dir="$PACKAGE_GIT_DIR" write-tree)"
PACKAGE_COMMIT="$(GIT_AUTHOR_NAME='Super Forms Release Builder' GIT_AUTHOR_EMAIL='release-builder@super-forms.invalid' GIT_COMMITTER_NAME='Super Forms Release Builder' GIT_COMMITTER_EMAIL='release-builder@super-forms.invalid' GIT_AUTHOR_DATE="$SOURCE_AUTHOR_DATE" GIT_COMMITTER_DATE="$SOURCE_COMMIT_DATE" git --git-dir="$PACKAGE_GIT_DIR" commit-tree "$TREE" -m "Customer package $COMMIT")"

ARCHIVE="$TMP/archive"
mkdir -p "$ARCHIVE"
git --git-dir="$PACKAGE_GIT_DIR" archive --format=tar --mtime="$SOURCE_COMMIT_DATE" "$PACKAGE_COMMIT" | tar -xf - -C "$ARCHIVE"
mkdir -p "$(dirname "$OUTPUT")"
TMP_OUTPUT="${OUTPUT}.tmp.$$"
rm -f "$TMP_OUTPUT"
(cd "$ARCHIVE" && LC_ALL=C find super-forms -type f -print | LC_ALL=C sort | zip -X -9 -q "$TMP_OUTPUT" -@)
BYTES="$(stat -c%s "$TMP_OUTPUT" 2>/dev/null || stat -f%z "$TMP_OUTPUT")"
if (( BYTES > MAX_CUSTOMER_ZIP_BYTES )); then
  rm -f "$TMP_OUTPUT"
  echo "customer package exceeds $MAX_CUSTOMER_ZIP_BYTES-byte ceiling" >&2
  exit 2
fi
SHA256="$(sha256sum "$TMP_OUTPUT" | cut -d' ' -f1)"
mv "$TMP_OUTPUT" "$OUTPUT"
printf 'built %s (%s bytes)\n' "$OUTPUT" "$BYTES"
printf 'sha256: %s\n' "$SHA256"
