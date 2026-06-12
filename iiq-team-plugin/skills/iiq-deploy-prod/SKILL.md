---
name: iiq-deploy-prod
description: Gated production deploy ritual for igniteiq.com — cornerstone hand-edit check, backup decision, staging green check, watched DEPLOY-PRODUCTION dispatch, 6-URL verification, cache prompt. Always use this instead of raw gh workflow run. Use when the user says "deploy to prod", "ship it", "push to production", or invokes "/iiq-deploy-prod".
---

# iiq-deploy-prod

*Ritual: 6 ordered steps; skipping any one has bitten us already (stale cache, clobbered cornerstones). Never dispatch `deploy-production.yml` outside this ritual.*

Context: production deploy rsyncs the theme + runs `wp igniteiq seed --force` (overwrites ACF `page_sections` on all 6 cornerstones) + `wp page-cache flush`. Full wiring in OPERATIONS.md → "Deploy flow".

Run the steps **in order**. Stop at any red unless the operator explicitly overrides.

## Step 1 — Cornerstone hand-edit check

`seed --force` wipes any WP-admin edits to cornerstone `page_sections`. Check whether anyone hand-edited since the last seed:

```bash
ssh igniteiq@igniteiq.ssh.wpengine.net 'cd sites/igniteiq && wp post list --post_type=page --fields=ID,post_name,post_modified'
```

Compare `post_modified` timestamps against the last production deploy time (`gh run list --workflow=deploy-production.yml --repo MLawler-IQ/ignite --limit 1`). If a cornerstone was modified after the last seed: **stop and ask** — those edits will be destroyed. The fix is to move the content into `inc/cli.php` (or Site Settings) first.

## Step 2 — Backup decision point

Ask: is this change risky (anything beyond purely additive PHP/template)? If yes: operator takes a WPE prod backup first — User Portal → Backups → Create. This is manual; wait for their confirmation. If purely additive: note "backup skipped — additive change" and continue.

## Step 3 — Confirm staging is green

Staging is the deploy preview. Verify the latest staging run succeeded and the cornerstones load:

```bash
gh run list --workflow=deploy-staging.yml --repo MLawler-IQ/ignite --limit 1
for p in / /how-it-works/ /ontology/ /company/ /contact/ /signin/; do
  printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' "https://igniteiqstg.wpenginepowered.com$p")" "$p"
done
```

All 6 must be 200 and the staging run green. Red → fix staging first; never deploy prod past a red staging.

## Step 4 — Dispatch + watch

```bash
gh workflow run deploy-production.yml -f confirm=DEPLOY-PRODUCTION --repo MLawler-IQ/ignite
gh run watch $(gh run list --workflow=deploy-production.yml --repo MLawler-IQ/ignite --limit 1 --json databaseId --jq '.[0].databaseId') --repo MLawler-IQ/ignite --exit-status
```

(The confirm gate string `DEPLOY-PRODUCTION` is checked in the job's `if:` — without it the run no-ops.) Watch to completion. On failure: report the failing step, do NOT retry blindly.

## Step 5 — Verify all 6 URLs return 200

```bash
for p in / /how-it-works/ /ontology/ /company/ /contact/ /signin/; do
  printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' "https://igniteiq.com$p?v=$(date +%s)")" "$p"
done
```

(`?v=` busts the Cloudflare edge so you're verifying origin, not stale cache.) Any non-200: report immediately — rollback is `git revert` + redeploy.

## Step 6 — Cache + verification prompts

- Visible change? Cloudflare edge serves stale HTML up to ~10 min (`max-age=600`). Offer `/iiq-purge-cf-cache`.
- Then prompt: run `/verify-iiq-fidelity` and `/visual-iiq-diff` to confirm fidelity on the live build.

## Closing check (R1)

Ask: **"Did this session change anything about live wiring — mail, DNS, deploy flow, secrets, editable-content surface? If yes, OPERATIONS.md must be updated in the same change set."** Don't end the ritual until answered.

## Don'ts

- Don't dispatch without steps 1–3 green (or an explicit operator override, stated back to them).
- Don't run `seed` or `rsync` by hand over SSH — the workflow is the only deploy path.
- Don't "Purge Everything" on Cloudflare — per-URL purge only (`/iiq-purge-cf-cache`).
