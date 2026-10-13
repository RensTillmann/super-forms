#!/usr/bin/env bash
# CI entry point for the privacy gate. Runs from the BASE checkout; the PR head is
# only read as git data (git log / git diff). Reads everything from env; never echoes it.
set -uo pipefail
SCAN="python3 ${SCANDIR:-$(dirname "$0")}/privacy_scan.py --require-denylist"
GITLEAKS="${GITLEAKS:-/tmp/gitleaks}"
fail=0
# Fail closed if the denylist is missing/empty (also the case for fork PRs).
printf '' | $SCAN text --label "in denylist self-check" >/dev/null || { echo "privacy-gate: ERROR PRIVACY_DENYLIST secret is missing or empty. Failing closed."; exit 2; }
git cat-file -e "${HEAD_SHA}^{commit}" 2>/dev/null || { echo "privacy-gate: ERROR PR head commit not fetched"; exit 2; }
mb=$(git merge-base "$BASE_SHA" "$HEAD_SHA") || { echo "privacy-gate: ERROR cannot compute merge base"; exit 2; }
$SCAN range "$mb" "$HEAD_SHA" || fail=1
printf '%s\n' "$HEAD_REF" | $SCAN text --label "in branch name" || fail=1
printf '%s\n' "$PR_TITLE" | $SCAN text --label "in PR title" || fail=1
printf '%s\n' "${PR_BODY:-}" | $SCAN text --label "in PR body" || fail=1
if [ -x "$GITLEAKS" ]; then
  "$GITLEAKS" git --no-banner --redact --log-opts="$mb..$HEAD_SHA" . || { echo "privacy-gate: gitleaks found a secret in the PR commits"; fail=1; }
  printf '%s\n%s\n' "$PR_TITLE" "${PR_BODY:-}" | "$GITLEAKS" stdin --no-banner --redact || { echo "privacy-gate: gitleaks found a secret in the PR title/body"; fail=1; }
else echo "privacy-gate: ERROR gitleaks missing"; fail=1; fi
[ $fail -eq 0 ] && echo "privacy-gate: PASSED" || { echo "privacy-gate: FAILED"; exit 1; }
