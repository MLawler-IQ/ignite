---
name: iiq-port-design
description: Orchestrate the full W2 design-export port loop for igniteiq.com — fetch handoff, diff, port, mandatory human review pause, commit/push, watch staging deploy, verify fidelity, visual diff. Use when a Claude Design handoff URL drops or the user says "port the latest design", "match the site to this export", or invokes "/iiq-port-design <handoff-url>".
---

# iiq-port-design

*Ritual: the 7-step loop with a mandatory human pause; running steps out of order produces unverified deploys. This orchestrates the 5 canonical port-loop skills — it adds sequencing and safety pauses, never replaces their logic.*

Usage: `/iiq-port-design <handoff-url>` (forms accepted are whatever `/fetch-iiq-design` accepts: full URL or bare handoff ID). No URL given → ask for it; check whether one is earlier in the conversation first.

**Safety prompt between every step:** after each step completes, summarize its output and ask the operator to continue / stop / redirect. Loop position is durable — if the operator stops, tell them which step to resume from (the manual chain below remains the documented fallback).

## The loop

### 1. Fetch

Run `/fetch-iiq-design <url>` → unpacks into `exports/<date>-handoff-*/`, repoints `exports/latest`, pre-generates DIFF.md. Pause: show the fetch summary.

### 2. Diff

Run `/diff-iiq-export` → DIFF.md against live staging (missing-on-staging backlog + extras). Pause: show the counts and the headline items.

### 3. Port

Run `/port-iiq-diff` → byte-accurate edits to `igniteiq/inc/cli.php` seed rows + `template-parts/*.php`, `php -l` on every modified file. It stops short of committing — by design.

### 4. MANDATORY PAUSE — operator reviews the git diff

Show `git diff --stat` and walk through the substantive hunks. The operator must explicitly approve before anything is committed. This pause is the human fidelity check — never skip it, never treat silence as approval.

### 5. Commit + push

```bash
git pull --rebase origin main
git push origin main
```

Commit first (subject ≤72 chars, body says *why*, matching `git log -10` tone). On rebase conflict: abort, re-read, re-apply, retry once, then stop and report (parity-plan §3). Push to main auto-deploys staging.

### 6. Wait for the staging deploy

```bash
gh run watch $(gh run list --workflow=deploy-staging.yml --repo MLawler-IQ/ignite --limit 1 --json databaseId --jq '.[0].databaseId') --repo MLawler-IQ/ignite --exit-status
```

~60s: rsync + `seed --force` + page-cache flush + 6-URL verify. Red run → stop, report the failing step.

### 7. Verify

Run `/verify-iiq-fidelity` (every export string present on rendered staging HTML), then `/visual-iiq-diff` (Playwright screenshots, all 6 pages, export vs staging — Mac-local only; skip with a note if Chromium isn't available, e.g. Cowork).

## Summary

End with: export ported (dir name), files touched, commit SHA, staging run result, fidelity verdict (string check + visual), any `// FIDELITY EXCEPTION`s added, and the next decision (ship to prod via `/iiq-deploy-prod`?).

## Manual fallback chain

`/fetch-iiq-design <url>` → `/diff-iiq-export` → `/port-iiq-diff` → human review → `git commit && git push` → `gh run watch` → `/verify-iiq-fidelity` → `/visual-iiq-diff`

## Don'ts

- Don't hand-edit toward the export outside `/port-iiq-diff` — its byte-accuracy rules ARE the fidelity guarantee.
- Don't commit before the step-4 review, ever.
- Don't deploy to production from this loop — staging only; prod is `/iiq-deploy-prod`, a separate decision.
