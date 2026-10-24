## What and why

Describe the change and why it is needed.

Fixes # (issue)

## Base branch

Pick one and say why. Rules: https://github.com/RensTillmann/super-forms/blob/lts/6.3.x/AGENTS.md

- [ ] `lts/6.3.x`: Stable (6.3.x). Security fixes, bug fixes, small requested improvements. Fixes go here first.
- [ ] `release/6.4.x`: Beta (6.4.0xx). Frozen: security and bug fixes only.
- [ ] `gitbook`: docs.super-forms.com. Changes under `gitbook-docs/` only.
- [ ] `alpha`: the rewrite. Not released.

Why this branch:

If this is a 6.4 fix for a bug that also exists in 6.3, link the `lts/6.3.x` PR here: #
Redo the fix on `release/6.4.x` (the plugin lives in `src/`). Do not cherry-pick.

## What was tested, and how

- Steps:
- Result:
