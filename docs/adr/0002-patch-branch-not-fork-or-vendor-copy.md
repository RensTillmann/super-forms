# ADR 0002 — Fix submission compatibility on local branches of the canonical repo, not a vendor copy or fork

Status: Accepted by Rens (2026-10-08). Supersedes the 2026-10-07 spec line "patch vendor copy in-tree + ADR" on Kanban t_6a30bd83.

The four submission compatibility fixes (email domain, currency normalization, empty code alphabet, day-less dates) and the follow-up upload/profile-mapper corrections are made as local commits on `fix/submission-compatibility-20261007` (LTS) and `fix/submission-compatibility-beta-20261007` (beta), worktrees of `RensTillmann/super-forms` built on the private 6.3.321 date hotfix (ADR 0001). We chose this because Super Forms is Rens's own product, so the canonical repo is the only place a fix can ship from, and the alternatives either lose the hotfix or create a second source of truth.

## Considered options

- **Patch the extracted copy in place** (`Downloads/super-forms (6)/super-forms`, 6.3.319). The original plan. Rejected: not a git repo, predates the 6.3.321 hotfix, and anything fixed there would have to be re-applied by hand to the real source anyway.
- **Fork.** Rejected: there is no third-party upstream to diverge from. A fork of your own repo is a second source that drifts and has to be merged back.
- **Push/PR to `origin` now.** Deferred, not rejected: it is the release path. Pushing, merging and publishing stay owner-gated until the release gates on T5 (t_19fdd526) pass.

## Consequences

- No push, merge or publication follows from this ADR. Merging these branches into `lts/6.3.x` / `release/6.4.x` is a separate owner decision.
- The regression proof is the real-WordPress submission loop and the shipped-client Node tests in these branches. The copied-code harness `sf_verify_20261007.php` is retired; it could never turn green from a product change.
- Each fix has to be ported to beta by hand under `src/`; there's no automatic sync between lanes.
