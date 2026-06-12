---
name: iiq-check-context
description: 5-point session preflight for igniteiq.com work — gh auth + workflow scope, SSH reachability to both WPE envs, clean working tree, OPERATIONS.md loaded, recent Actions failures. Use at the start of any session, or when the user says "check context", "preflight", "is everything wired up", or invokes "/iiq-check-context".
---

# iiq-check-context

*Ritual: 5-point preflight nobody remembers consistently. Most "it's broken" reports are a stale auth or unreachable SSH — this catches them in 30 seconds.*

Run all 5 checks, then print **one green/red summary table**. Don't fix anything unprompted — report, and offer the fix (`/iiq-setup` re-run covers most).

## Check 1 — gh authenticated with workflow scope

```bash
gh auth status
```

Green: authenticated AND token scopes include `workflow` (required for production deploy dispatch). Red: not logged in, or scope missing → fix is `gh auth login` / `gh auth refresh -s workflow`.

## Check 2 — SSH reachable, both envs

```bash
ssh -o BatchMode=yes -o ConnectTimeout=10 igniteiqstg@igniteiqstg.ssh.wpengine.net 'echo staging-ok'
ssh -o BatchMode=yes -o ConnectTimeout=10 igniteiq@igniteiq.ssh.wpengine.net 'echo prod-ok'
```

Green: both print their ok line. Red: key not registered in WPE portal or `~/.ssh/config` missing the host block (ONBOARDING.md §1.4, `/iiq-setup` step 4).

## Check 3 — Working tree clean + up to date with origin/main

```bash
git status --short
git fetch origin main --quiet && git rev-list --left-right --count origin/main...HEAD
```

Green: no uncommitted changes, `0	0` (neither ahead nor behind). Yellow: ahead (unpushed commits) or dirty — note it; uncommitted work older than a working session is a smell (parity-plan §9d). Red: behind origin/main → `git pull --rebase origin main` before doing anything.

## Check 4 — OPERATIONS.md loaded

Confirm OPERATIONS.md is in context by citing one live fact from it without re-reading the file — e.g. "SMTP constants live in `wp-content/mu-plugins/iiq-smtp.php`" or "Cloudflare zone_id is `5f6003a1d80fbbca4fb4e687e47c8ba3`". If you cannot cite a fact, it didn't auto-load: red — the operator is probably not inside the repo (`CLAUDE.md` imports it via `@OPERATIONS.md`).

## Check 5 — Recent Actions failures

```bash
gh run list --repo MLawler-IQ/ignite --limit 10
```

Green: no failed runs in the last 10. Red: any `failure` → name the workflow + run, link `gh run view <id>`, flag before the operator pushes anything on top.

## Output format

```
IIQ context check — <date>
  [✅/❌] gh auth (workflow scope)
  [✅/❌] SSH staging · [✅/❌] SSH production
  [✅/⚠️/❌] git tree (clean / dirty / behind)
  [✅/❌] OPERATIONS.md loaded — "<one cited fact>"
  [✅/❌] Actions: last 10 runs (<n> failed)
Verdict: GREEN — go / RED — fix <x> first (try /iiq-setup)
```
