# Super Forms: rules for AI agents and contributors

This file is identical on `lts/6.3.x`, `release/6.4.x` and `gitbook`. Read it before you branch, commit or open a PR.

## Branches

| Branch | What it is | What goes there |
|---|---|---|
| `lts/6.3.x` | Stable channel, 6.3.x | Security fixes, bug fixes, small requested improvements |
| `release/6.4.x` | Beta channel, 6.4.0xx, frozen | Security and bug fixes only |
| `gitbook` | docs.super-forms.com | `gitbook-docs/` only |
| `alpha` | The rewrite: not released, version number not decided | Rewrite work only. Never call it v7 |
| `stable`, `beta` | Old 6.4.200/6.4.201 trunk, never released, being archived | Nothing. Never base work on them |

`lts/6.3.x` is GitHub's default branch. Still pick the base branch explicitly for every PR: the default is wrong for 6.4-only fixes, docs and rewrite work.

## Where a change goes

- Fix `lts/6.3.x` first. If 6.4 has the same bug, open a second PR to `release/6.4.x` and redo the fix there (the layouts differ: 6.4 keeps the plugin in `src/`). Don't blind-cherry-pick.
- Never cherry-pick from `alpha`.
- Docs change only through a PR to `gitbook`, touching `gitbook-docs/` only. Plugin branches never change `gitbook-docs/`.
- Version numbers: 6.3 stays 6.3.xxx and 6.4 stays 6.4.0xx.

## Never break existing sites

- Stored form, entry and settings keys, hooks, options and database tables must stay readable by the new code.
- New options need safe defaults, so a site that never saved them behaves exactly as before.

## Privacy

No customer names, companies, domains, e-mail addresses or ticket numbers in commits, branch names, PR text, code comments or tests. Use neutral placeholders such as `example.com`.

## PR checklist

- [ ] Base branch, and why this branch.
- [ ] What was tested, and how.
- [ ] Before you open the PR, check that there is no customer data in the diff, the commits or the PR text. This is a check only: don't write it in the PR.
