# Parity Plan — concurrency + safety conventions for two full committers

> Companion to `ONBOARDING.md` (setup) and `.planning/iiq-team-plugin-spec.md` (tooling).
> Scope: the conventions, repo settings, and detection mechanisms that keep two equally-trusted operators (Matt + Scott) from stepping on each other or the production site.
> Status: PLANNED — each section ends with an "Apply" step; nothing here is live until applied.

---

## 1. Branch policy

**Default: direct-push to `main`** for routine edits (copy changes, link swaps, template tweaks). Push to main auto-deploys staging; prod stays behind the manual gate. This preserves the fast loop that makes self-serve worthwhile.

**Branch + PR required** when the change touches any sensitive path:

| Path | Why |
|---|---|
| `.github/workflows/**` | Deploy contract |
| `igniteiq/inc/mail.php` | Production email path |
| `igniteiq/inc/contact-form.php` | Production email path + spam defenses |
| `igniteiq/inc/cli.php` | All cornerstone seed copy incl. homepage hero (file-level — CODEOWNERS can't scope to the hero rows alone; accepted, since W2 ports already get human review) |
| `igniteiq/page-signin.php` | Customer-access surface |
| `OPERATIONS.md` | Shared source of truth — changes deserve a second pair of eyes |
| Anything SEO-sensitive (see §10) | Rank impact |

**Proposed `CODEOWNERS`** (new file at repo root):

```
# Sensitive paths — require Matt's review when changed via PR
/.github/workflows/             @MLawler-IQ
/igniteiq/inc/mail.php          @MLawler-IQ
/igniteiq/inc/contact-form.php  @MLawler-IQ
/igniteiq/inc/cli.php           @MLawler-IQ
/igniteiq/page-signin.php       @MLawler-IQ
/OPERATIONS.md                  @MLawler-IQ
```

**Apply:** commit `CODEOWNERS`; encode the branch-vs-direct decision rule in the editor agent's system prompt (plugin spec §3).

---

## 2. Branch protection

**The honest tension:** classic branch protection can't say "require PRs only for these paths." If we require PR reviews on `main`, direct-push dies for routine edits too — defeating the point.

**Decision — two layers:**

- **Layer 1 (now, free):** protect `main` with the rules that don't break direct-push: **block force-pushes** and **block branch deletion**. CODEOWNERS still auto-assigns Matt whenever a PR *is* opened.
- **Layer 2 (when plan allows):** a **push ruleset** restricting direct pushes that modify the §1 sensitive paths — exactly the path-scoped enforcement we want. ⚠️ Rulesets with path conditions on a **private** repo require GitHub Team. If `MLawler-IQ` is on Free and the repo is private, Layer 2 waits for an upgrade; until then the agent's system prompt + Slack notifications (§6) are the enforcement.

**Apply:** Settings → Branches → add rule for `main`: ✅ Block force pushes, ✅ Restrict deletions, ❌ Require PR (deliberately off). Revisit push rulesets after checking the org's plan.

---

## 3. Pull-rebase-before-push

Every push, by every operator and every agent session:

```bash
git pull --rebase origin main   # ALWAYS, immediately before push
git push origin main
```

**On rebase conflict, the agent must:** abort the rebase (`git rebase --abort`), re-read the conflicting files fresh, re-apply its edit on the new tip, retry **once**. If it conflicts again → stop, report to the operator, never resolve by force. **Never** `git push --force`, **never** `git reset --hard`.

**Apply:** encoded in the editor agent's system prompt + the agent's Bash deny list (plugin spec §2/§4).

---

## 4. WP admin audit trail

**Decision: Simple History** (free, actively maintained, logs post/option/plugin/user events with user + timestamp + diff, lightweight). Stream's cloud half is abandoned and the plugin is heavier for the same coverage we need.

```bash
# both envs
ssh <env> 'cd sites/<site> && wp plugin install simple-history --activate'
```

Retention: default 60 days is fine — long enough to investigate "who changed this," short enough to keep the DB lean. WP admin → dashboard → Simple History shows the feed.

**Apply:** one wp-cli command per env (above) + note in OPERATIONS.md.

---

## 5. ACF Site Settings rollback

**Problem:** ACF options-page values (e.g. `contact_email`, future `footer_columns`) live in `wp_options` with **no revision history** — a bad save is gone.

**Options considered:**
- *ACF Local JSON* — versions field **definitions**, not values. Wrong tool. ❌
- *Revision plugins* — WP revisions cover posts; options-page support is unreliable third-party territory. ❌
- *Nightly snapshot via scheduled GH Action* — reuses the SSH plumbing every workflow already has, zero new WP plugins, restore path is a documented `wp eval-file`. ✅ **Chosen.**

**Design:** scheduled workflow (`option-snapshot.yml`, cron `0 7 * * *`) SSHes to both envs, dumps the managed `options_*` keys to JSON (`wp option list --search='options_*' --format=json`), commits to a `snapshots` branch (one file per env per day, pruned at 90 days). **Restore:** copy the wanted value out of the snapshot, apply with `update_field()` via `wp eval-file` (never raw `wp option update` — skips ACF's key reference; see OPERATIONS.md Don't-touch list).

**Apply:** new workflow file (build alongside the plugin; same session is fine).

---

## 6. Deploy notifications

Both deploy workflows get a final step posting to Slack:

```yaml
- name: Notify Slack
  if: always()
  run: |
    curl -s -X POST "${{ secrets.SLACK_WEBHOOK_URL }}" -H 'Content-Type: application/json' \
      --data "{\"text\":\"${{ job.status == 'success' && '✅' || '❌' }} ${{ github.workflow }} — ${{ github.actor }} — ${{ github.event.head_commit.message || 'manual dispatch' }}\"}"
```

Channel: `#iiq-website` (create if absent). Each operator sees what the other shipped, automatically, including failures.

**Apply:** create Slack incoming webhook → `gh secret set SLACK_WEBHOOK_URL` → add the step to both workflows (a §1 sensitive path — Matt does this one).

---

## 7. Cross-env drift detection

Once content is editable in WP admin, staging and prod `wp_options` diverge silently. **`/iiq-env-diff`** (plugin spec §3): fetches the managed `options_*` keys from both envs over SSH, normalized JSON diff, human-readable report. Run on-demand any time; plus a nightly scheduled workflow reusing the §5 snapshot data — if staging ≠ prod for any managed key, post the diff to Slack.

**Apply:** skill ships with the plugin; nightly job piggybacks on `option-snapshot.yml`.

---

## 8. Secret rotation cadence

| Secret | Cadence | Procedure |
|---|---|---|
| `IIQ_SMTP_PASS` (Gmail app password) | Every 6 months | `/iiq-rotate-smtp-pass` (guided) |
| `WPE_SSH_KEY` | Annually | New keypair → WPE portal → `gh secret set` |
| Cloudflare API token | Annually (set expiry at creation) | New token at dash.cloudflare.com → used ad-hoc, never stored |
| `SLACK_WEBHOOK_URL` | On compromise only | Regenerate webhook |

**Plus the standing rule** (already an OPERATIONS.md invariant): any credential that appears in a chat window — Claude, Slack, email — is treated as exposed and rotated at end of session.

**Apply:** calendar reminders (Matt); procedures already documented.

---

## 9. The four concurrency scenarios (R2)

| # | Scenario | What happens without mitigation | Mitigation |
|---|---|---|---|
| a | Both push to main within minutes | Second push rejected (non-fast-forward) — no corruption, but a confused operator might force-push | §3 pull-rebase makes the second push clean; force-push is deny-listed AND blocked by §2 branch protection. Deploys serialize via `concurrency: group: deploy-staging, cancel-in-progress: false` — both runs execute in order; last writer wins, and since each deploys the full repo tip, the final state includes both edits. |
| b | One mid-deploy while the other pushes | Two staging runs queue; no overlap, no torn rsync | Same concurrency group queues run 2 behind run 1 (verified in both workflow files). Prod has its own `deploy-production` group. Slack (§6) makes the overlap visible to both. |
| c | Scott saves a WP-admin field while a deploy runs `seed --force` 10 min later | **Depends where the field lives** (OPERATIONS.md "what seed --force overwrites"): Site Settings options (e.g. `contact_email`) → **survives**, seed never touches options. Cornerstone `page_sections` → **wiped** by the seed. | Rule (already in OPERATIONS.md invariants): never hand-edit cornerstone `page_sections` in admin; edit the seed in `cli.php` instead. Editable content lives in Site Settings only (§10 re-seed decision). Simple History (§4) shows what was lost if someone forgets; §5 snapshots restore option values. |
| d | Scott's agent edits a file Matt has uncommitted changes to (different machines) | Scott's agent sees only origin/main — Matt's uncommitted work is invisible. Scott pushes; later Matt's `pull --rebase` hits a conflict in his working tree | Convention: commit small and often — uncommitted work older than a working session is a smell. Matt resolves locally (his conflict, his context). The agent-side rule (§3) prevents either side from forcing through. |

---

## 10. Content boundary decisions (R4)

- **Re-seed clobber — decision (b): editable content lives in Site Settings options only.** Seed stays `--force` so a deploy always reflects the code (the fidelity invariant depends on this). Anything the team wants admin-editable gets lifted into a Site Settings field (pattern proven by `contact_email`); cornerstone `page_sections` remain code-owned. Documented in OPERATIONS.md (already) + agent prompt.
- **SEO-sensitive fields — mechanism: agent-prompt enforcement + CODEOWNERS overlap.** Title tags, meta descriptions, H1s, slugs, canonical URLs, internal anchors: the editor agent warns and requires the operator to type `SEO-REVIEW` acknowledgment before committing changes to them (system-prompt rule); most of these live in `cli.php`/page templates already under CODEOWNERS. A pre-commit lint was considered and rejected — "SEO-sensitive" isn't reliably machine-detectable, and a noisy lint gets disabled.
- **Voice** — enforced in the agent's system prompt with examples (plugin spec §2); not a repo mechanism.

---

## 11. Housekeeping

`.gitignore` currently misses two things:

```
# add:
.claude/settings.local.json
```

(`settings.local.json` holds per-operator irreversible-op permissions and must never be committed; today it's untracked-but-unignored — one careless `git add .` from disaster.) `.planning/` stays **committed deliberately** — it's shared planning context.

**Apply:** one-line .gitignore addition with the next commit.

---

## 12. Conformance matrix

| Requirement | Handled by | Status |
|---|---|---|
| R1 context parity | OPERATIONS.md auto-import (live); maintenance rule: any session that changes live wiring updates OPERATIONS.md in the same commit — encoded in agent prompt; `/iiq-deploy-prod` ends with a "did OPERATIONS.md change?" check | Planned (agent prompt) |
| R1 doc set | CLAUDE.md + OPERATIONS.md + AGENTS.md (already exists) — capped at three; no CONTRIBUTING.md (parity-plan covers conventions) | Decided |
| R2 (a)(b) | §3 pull-rebase + §2 protection + workflow concurrency groups | (a)(b) live in workflows already; §2/§3 planned |
| R2 (c) | §9c + §10 re-seed decision + §4 audit + §5 snapshots | Planned |
| R2 (d) | §9d convention | Planned (agent prompt) |
| R3 hard denies | Agent tool restrictions (plugin spec §2/§4) | Planned |
| R3 branch protection | §2 — Layer 1 now; Layer 2 **fails open on Free-plan private repo** → follow-up: check org plan | Partial by design |
| R3 audit trail | §4 Simple History | Planned |
| R3 secret hygiene | §8 cadence + chat-exposure rule | Rule live; cadence planned |
| R3 rollback | Code: git revert (live). Options: §5 snapshots | Planned |
| R4 voice / SEO / sensitive paths | §10 + §1 CODEOWNERS + agent prompt | Planned |
| R4 drift | §7 /iiq-env-diff + nightly job | Planned |

**Fails-open items (follow-ups):** Layer-2 push rulesets pending plan check; everything marked "agent prompt" is only as strong as the agent honoring it until rulesets enforce server-side.

---

## Application order

1. **Now (no dependencies):** `.gitignore` fix (§11) · `CODEOWNERS` (§1) · branch protection Layer 1 (§2) · Simple History install (§4)
2. **With the plugin build:** agent prompt rules (§3, §9d, §10, R1 maintenance) · `/iiq-env-diff` (§7)
3. **Needs Matt (sensitive paths):** Slack webhook + workflow steps (§6) · `option-snapshot.yml` (§5)
4. **Pending org-plan check:** push rulesets (§2 Layer 2)
