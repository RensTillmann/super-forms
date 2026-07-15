#!/usr/bin/env bash
set -euo pipefail

[[ "$(node --version)" == 'v24.13.1' ]]
[[ "$(npm --version)" == '11.8.0' ]]
[[ "$(git --version)" == 'git version 2.43.0' ]]
[[ "$(dpkg-query -W -f='${Version}' zip)" == '3.0-13' ]]
[[ "$(zip -v | sed -n '2p' | tr -d '\r')" == 'This is Zip 3.0 (July 5th 2008), by Info-ZIP.' ]]
[[ "$(unzip -v | sed -n '1p' | tr -d '\r')" == 'UnZip 6.00 of 20 April 2009, by Debian. Original by Info-ZIP.' ]]

GIT_EXEC_PATH="$(git --exec-path)"
[[ -x "$GIT_EXEC_PATH/git-commit" ]]
[[ -d /usr/local/share/git-core/templates ]]

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

mkdir -p "$TMP/source"
git -C "$TMP/source" init -q
git -C "$TMP/source" config user.name 'Release Image Contract'
git -C "$TMP/source" config user.email 'release-image-contract@super-forms.invalid'
printf 'contract\n' > "$TMP/source/file.txt"
git -C "$TMP/source" add file.txt
git -C "$TMP/source" commit -qm 'contract source'
[[ -z "$(git -C "$TMP/source" status --porcelain --untracked-files=all)" ]]
SOURCE_COMMIT="$(git -C "$TMP/source" rev-parse HEAD^{commit})"
[[ "$(git -C "$TMP/source" show -s --format=%s "$SOURCE_COMMIT")" == 'contract source' ]]
git -C "$TMP/source" archive --format=tar "$SOURCE_COMMIT" > "$TMP/source.tar"
tar -tf "$TMP/source.tar" | grep -qx 'file.txt'

git init -q --bare "$TMP/package.git"
mkdir -p "$TMP/stage"
printf 'package\n' > "$TMP/stage/package.txt"
GIT_INDEX_FILE="$TMP/index" GIT_WORK_TREE="$TMP/stage" git --git-dir="$TMP/package.git" read-tree --empty
GIT_INDEX_FILE="$TMP/index" GIT_WORK_TREE="$TMP/stage" git --git-dir="$TMP/package.git" add --all -- package.txt
TREE="$(GIT_INDEX_FILE="$TMP/index" git --git-dir="$TMP/package.git" write-tree)"
PACKAGE_COMMIT="$(GIT_AUTHOR_NAME='Release Image Contract' GIT_AUTHOR_EMAIL='release-image-contract@super-forms.invalid' GIT_COMMITTER_NAME='Release Image Contract' GIT_COMMITTER_EMAIL='release-image-contract@super-forms.invalid' git --git-dir="$TMP/package.git" commit-tree "$TREE" -m 'contract package')"
[[ "$(git --git-dir="$TMP/package.git" rev-parse "$PACKAGE_COMMIT^{commit}")" == "$PACKAGE_COMMIT" ]]
git --git-dir="$TMP/package.git" archive --format=tar "$PACKAGE_COMMIT" > "$TMP/package.tar"
tar -tf "$TMP/package.tar" | grep -qx 'package.txt'

mkdir -p "$TMP/archive"
printf 'zip-contract\n' > "$TMP/archive/file.txt"
(cd "$TMP/archive" && zip -X -q "$TMP/contract.zip" file.txt)
[[ "$(unzip -p "$TMP/contract.zip" file.txt)" == 'zip-contract' ]]
mkdir -p "$TMP/tar-out"
tar -cf "$TMP/contract.tar" -C "$TMP/archive" file.txt
tar -xf "$TMP/contract.tar" -C "$TMP/tar-out"
cmp "$TMP/archive/file.txt" "$TMP/tar-out/file.txt"
[[ "$(bash -c 'printf bash-contract')" == 'bash-contract' ]]

for forbidden in php cc make curl xz; do
  ! command -v "$forbidden" >/dev/null 2>&1 || {
    printf 'forbidden tool present: %s\n' "$forbidden" >&2
    exit 1
  }
done

printf 'release image contract passed\n'
