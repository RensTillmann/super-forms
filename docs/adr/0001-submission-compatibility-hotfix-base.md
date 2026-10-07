# ADR 0001 — Preserve the private hotfix before submission compatibility fixes

Status: Accepted by Rens in the current implementation conversation.

## Context

`RensTillmann/super-forms` is the canonical source. LTS `lts/6.3.x` uses a flat runtime tree; beta `release/6.4.x` keeps runtime under `src/`. Installed/A-B copies are evidence, not release source.

The private 6.3.321 date fix existed only in the installed/A-B copy. A release from 6.3.320 source would omit it. Existing local LTS and beta checkouts also contain unrelated PDF edits, so whole-file replacement or committing their entire dirty state would mix work.

## Decision

1. Preserve the exact missing/empty-format fallback as a standalone LTS commit (`ce0c12d`). Its fallback is `dd-mm-yy`, including empty/missing custom formats.
2. Build the four verified compatibility fixes on a separate Git worktree/branch above that commit. Keep existing dirty checkouts intact.
3. Port narrow reviewed runtime changes to beta, mapping paths under `src/` and adapting genuine version differences. A path difference does not inherently make cherry-picking impossible, but a clean default cherry-pick must not be assumed. In this run, checked path-mapped patches handled Ajax/client changes; the code-generator change required a manual port that preserved beta-specific optional-setting handling.
4. Keep strict server rejection. Email domains with underscores/edge hyphens fail in the client; currency normalization uses saved field syntax for validation only; empty letters-only code alphabets share an uppercase fallback between preview/claim; omitted date components use the first day/month, while explicitly invalid components remain rejected.
5. Client and server consume the same email/currency fixture table. Real WordPress submission checks exercise the PHP behavior; complete-client VM tests exercise the JS behavior.

## Consequences and limits

- Unrelated PDF changes and historical installed/A-B evidence are preserved.
- The hotfix remains separately traceable and must be present in both channel candidates.
- Currency display/submission bytes are retained; normalization does not truncate amounts or loosen non-currency/choice validation.
- Tests bind actual loaded class paths and source hashes. Copied regex/generator snippets are not regression proof.
- Local commits and focused tests do not authorize publishing, deployment or customer actions. The established release pipeline still requires its full suites, exact package/browser/upgrade evidence, review and operator approval.
