#!/usr/bin/env bash
# Build the customer release zip reproducibly from this branch's src/ tree.
#
# Usage:
#   ./build-release-zip.sh [output.zip] [ref]
#
#   output.zip  defaults to dist/super-forms-release.zip
#               (rename per channel: super-forms.zip / super-forms-beta.zip)
#   ref         defaults to HEAD (pass a tag like v6.4.005-beta for rebuilds)
#
# Exclusions (docs, pdf-generator fonts, *.po/*.pot) are enforced by
# src/.gitattributes export-ignore rules, so ANY `git archive` of the src/
# tree produces the same lean layout. The size assertion below is the
# backstop: a zip above the limit means the exclusions were bypassed or a
# large payload was committed, and the zip would break manual wp-admin
# uploads on most hosts.
set -euo pipefail

out="${1:-dist/super-forms-release.zip}"
ref="${2:-HEAD}"
max_bytes=$((50 * 1024 * 1024))

mkdir -p "$(dirname "$out")"
git archive --format=zip --prefix=super-forms/ -o "$out" "$ref:src"

bytes=$(stat -c%s "$out" 2>/dev/null || stat -f%z "$out")
if [ "$bytes" -gt "$max_bytes" ]; then
  echo "ERROR: $out is $((bytes / 1024 / 1024)) MB (limit $((max_bytes / 1024 / 1024)) MB)." >&2
  echo "Packaging exclusions missing? Check src/.gitattributes export-ignore rules." >&2
  exit 1
fi

echo "built $out ($((bytes / 1024 / 1024)) MB, $bytes bytes)"
echo "sha256: $(sha256sum "$out" 2>/dev/null | cut -d' ' -f1 || shasum -a 256 "$out" | cut -d' ' -f1)"
