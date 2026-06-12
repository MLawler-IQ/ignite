# iiq-team Plugin Spec

> Design for the Claude Code plugin that gives any operator (Matt, Scott, future hires) one-click parity: the editor agent, the ritual skills, and the bootstrap that installs everything else.
> Status: SPEC ONLY — implementation happens in a follow-up session after review.
> Structural template: `cowork-plugin/igniteiq-port/` (already uses the standard Claude Code plugin format).

---

## 1. Directory layout + manifest

```
iiq-team-plugin/                      # repo root, sibling of cowork-plugin/
├── .claude-plugin/
│   └── plugin.json
├── CHANGELOG.md
├── README.md
├── agents/
│   └── iiq-web-editor.md
└── skills/
    ├── iiq-setup/SKILL.md
    ├── iiq-check-context/SKILL.md
    ├── iiq-deploy-prod/SKILL.md
    ├── iiq-env-diff/SKILL.md
    ├── iiq-verify-mail/SKILL.md
    ├── iiq-rotate-smtp-pass/SKILL.md
    ├── iiq-publish-dns/SKILL.md
    ├── iiq-purge-cf-cache/SKILL.md
    ├── iiq-port-design/SKILL.md
    ├── fetch-iiq-design/SKILL.md      # synced from ~/.claude/skills/ (canonical)
    ├── diff-iiq-export/SKILL.md       # synced
    ├── port-iiq-diff/SKILL.md         # synced
    ├── verify-iiq-fidelity/SKILL.md   # synced
    └── visual-iiq-diff/SKILL.md       # synced
```

**`.claude-plugin/plugin.json`** (same shape as the Cowork plugin's confirmed manifest):

```json
{
  "name": "iiq-team",
  "version": "0.1.0",
  "description": "Team-parity toolkit for operating igniteiq.com via Claude Code: the iiq-web-editor agent (voice + fidelity + deploy discipline), one-time /iiq-setup bootstrap, and ritual skills for deploys, verification, mail, DNS, and drift detection. Bundles the 5 design-export port-loop skills.",
  "author": { "name": "IgniteIQ" }
}
```

---

## 2. The agent: `agents/iiq-web-editor.md`

Frontmatter (format confirmed from `~/.claude/agents/*.md`):

```yaml
---
name: iiq-web-editor
description: Edits igniteiq.com theme code and content with voice, fidelity, and deploy discipline. Use for any ad-hoc website change. Routes Claude Design handoff URLs into the port loop instead of ad-hoc editing.
tools: Read, Edit, Write, Grep, Glob, Bash
color: red
---
```

> Note: the `tools` field is tool-level only. Path-level and command-level restriction comes from two enforcing layers: the system-prompt rules below AND the settings deny rules in §4. Both are specified so a prompt slip doesn't equal a capability slip.

### System prompt — required sections

**Identity + voice.** IgniteIQ voice: bold, direct, ownership-focused. Short declarative sentences. Active voice. No filler, no hedging.
- Positive examples (from live copy): *"Own your intelligence."* · *"Let's talk. About your data, your decisions, and what compounds when both are yours."* · *"Intelligence infrastructure for home services."*
- Negative counter-examples: *"We provide comprehensive intelligence solutions"* (filler + services-speak) · *"Our team can help you leverage your data"* (hedged, passive value) · *"Industry-leading platform for digital transformation"* (jargon, no claim)
- Language rules: say *systems, infrastructure, we build, investment*; never *services, deliverables, retainer, vendor*.

**Fidelity protocol.** The WP build must match `exports/latest/` byte-accurately. Any intentional divergence gets a `// FIDELITY EXCEPTION:` comment with a one-line reason. Live examples to mirror: `template-parts/nav.php` (sign-in retarget to Studio) and `template-parts/forms/contact.php` (aside collapse). After visible changes: prompt the operator to run `/verify-iiq-fidelity` + `/visual-iiq-diff`.

**Edit discipline.** Always `php -l` every modified PHP file with the Local-shipped binary (path in CLAUDE.md) before committing. Never commit failing lint.

**Git discipline.** `git pull --rebase origin main` immediately before every push. On conflict: abort, re-read, re-apply, retry once, then stop and report. Never force-push, never reset --hard. Commit style: subject ≤72 chars, body explains *why*; match `git log -10` tone.

**Branch policy** (cross-ref parity-plan §1): direct-push for routine edits; branch + PR when touching `.github/workflows/`, `inc/mail.php`, `inc/contact-form.php`, `inc/cli.php`, `page-signin.php`, `OPERATIONS.md`, or SEO-sensitive fields.

**SEO gate** (parity-plan §10): before committing changes to title tags, meta descriptions, H1s, slugs, canonicals, or internal anchor text — warn the operator and require they type `SEO-REVIEW` to acknowledge.

**Cache awareness.** After any prod deploy, tell the operator the Cloudflare edge may serve stale HTML up to ~10 min; offer `/iiq-purge-cf-cache`.

**OPERATIONS.md maintenance** (parity-plan §12 R1): if the session changed anything about live wiring (mail, DNS, deploy flow, secrets, editable-content surface), update OPERATIONS.md in the same commit.

**Workflow recognition (W1 vs W2).** If the operator's message contains a Claude Design handoff URL, or phrasing like *"match the site to this export"* / *"port the latest design"* → this is W2: run `/iiq-port-design <url>` (or step the port loop manually). Do NOT hand-edit toward a design export as if it were an ad-hoc request — the port loop's diff/verify steps exist to guarantee fidelity.

### Tool restrictions (enforced by prompt here + settings in §4)

| | Allowed | Denied |
|---|---|---|
| Read/Edit | `igniteiq/inc/*`, `igniteiq/template-parts/**`, `igniteiq/assets/css/*`, `igniteiq/assets/js/*` (not minified bundles), `igniteiq/page-*.php`, `OPERATIONS.md`, `CLAUDE.md`, `.planning/**` | `wp-content/mu-plugins/**` (remote), `.github/workflows/**`, any file containing `IIQ_SMTP_*` values or DKIM/Cloudflare credentials, `.claude/settings.json` |
| Bash | `git status/diff/add/commit/log/pull`, `git push origin main`, `php -l`, `gh run list/view/watch`, `gh workflow run` (the 3 known workflows only), read-only `curl` | `rm`, `git push --force`, `git reset --hard`, `gh secret set`, raw Cloudflare API writes (must go through `/iiq-publish-dns`) |

---

## 3. Skills — every one is a ritual, none is a content category

Ad-hoc content edits have **no skills** — they flow through the agent (W1). Each skill below encodes a multi-step sequence that's easy to get wrong, with the justification stated.

### Bootstrap

**`/iiq-setup`** — *Ritual: environment bootstrap, 8 steps in order, partial completion leaves a confusing half-configured machine.*
Steps: detect repo root → `npm install` → `npx playwright install chromium` → write `~/.ssh/config` WPE entries if missing (ask first) → verify `gh auth status` + workflow scope (guide login if not) → SSH no-op to both WPE envs → write starter `.claude/settings.local.json` from the template in §4 (operator confirms each irreversible permission) → run `/iiq-check-context` and print summary. Idempotent. Only prerequisite it can't automate: `brew install node` (needs admin password).

### Pre-flight + state

**`/iiq-check-context`** — *Ritual: 5-point preflight nobody remembers consistently.* gh authed (workflow scope)? SSH reachable both envs? Working tree clean + up to date with origin/main? OPERATIONS.md loaded (cite one fact)? Any failed recent Actions runs? One green/red summary.

**`/iiq-env-diff`** — *Ritual: cross-env comparison with normalization that's fiddly to redo by hand.* Fetch managed `options_*` keys from both envs (`wp option list --search='options_*' --format=json` over SSH), normalize, diff, report per-key. Cross-ref parity-plan §7 for the nightly variant.

### Deploy + verification

**`/iiq-deploy-prod`** — *Ritual: 6 ordered steps; skipping any one has bitten us already (stale cache, clobbered cornerstones).*
(1) Cornerstone hand-edit check (`wp post list --fields=post_modified` vs last seed) → (2) backup decision point (WPE portal snapshot for risky changes) → (3) confirm staging is green → (4) dispatch `deploy-production.yml -f confirm=DEPLOY-PRODUCTION`, watch to completion → (5) verify all 6 URLs return 200 → (6) prompt: visible change? offer `/iiq-purge-cf-cache`, then `/verify-iiq-fidelity` + `/visual-iiq-diff`. Ends with the R1 check: "did this session change live wiring? update OPERATIONS.md."

**`/verify-iiq-fidelity`** *(bundled as-is from `~/.claude/skills/`)* — export-string check on rendered staging HTML.

**`/visual-iiq-diff`** *(bundled as-is)* — Playwright screenshots, all 6 pages, export vs staging, per-page gap report. Requires Chromium (installed by `/iiq-setup`); Mac-local only.

> **Naming decision (deviation from earlier draft):** the two verification skills keep their original names only — no `/iiq-verify-fidelity`/`/iiq-visual-diff` wrapper aliases. One name per behavior; two names invite drift between "the alias" and "the real one." `/iiq-deploy-prod` and the agent prompt reference the original names.

### Mail + DNS

**`/iiq-verify-mail`** — *Ritual: nonce-scrape + backdated timestamp + marker + inbox check is 5 fiddly steps.* Submits a test contact form (GET page → scrape nonce → POST admin-ajax with marker + backdated `rendered_at`) to a chosen env; verifies delivery via Gmail MCP if available, else prints the marker for manual inbox search. The exact recipe lives in OPERATIONS.md → "Submit a test contact form."

**`/iiq-rotate-smtp-pass`** — *Ritual: secret handling across 3 systems; a missed step silently breaks all contact-form mail.* Guide: new app password at myaccount.google.com → `gh secret set IIQ_SMTP_PASS` → `gh workflow run configure-mail.yml` for staging AND production with `send_test=true` → confirm both test mails arrive → remind: revoke the old app password.

**`/iiq-publish-dns`** — *Ritual: raw API calls against the zone are irreversible and unaudited; this wrapper is the only sanctioned path.* Token handling: prompt for `CLOUDFLARE_API_TOKEN` (never stored, never committed; "rotate after chat exposure" reminder at exit). Supports list/create/update for TXT/A/CNAME (SPF/DKIM/DMARC presets with correct names + zone_id baked in). **Refuses MX changes** without a typed confirmation (`CHANGE-MX`) — broken MX = lost company mail.

**`/iiq-purge-cf-cache`** — *Ritual: per-URL purge with the right zone_id; "Purge Everything" is the tempting wrong answer.* Purges the named URLs (defaults: `/` + the changed pages) via the CF API; falls back to dashboard clickpath instructions if no token.

### Design-export port loop (W2)

Bundled **unchanged** (synced from `~/.claude/skills/`, the canonical sources, by the build script — pattern proven by the Cowork build):
- `/fetch-iiq-design` — handoff URL → `exports/<date>/`, repoint `exports/latest`
- `/diff-iiq-export` — DIFF.md against staging
- `/port-iiq-diff` — byte-accurate edits to `cli.php` + template-parts, php-lint, stops before commit
- `/verify-iiq-fidelity`, `/visual-iiq-diff` — see above

**`/iiq-port-design <handoff-url>`** — *Ritual: the 7-step loop with a mandatory human pause; running steps out of order produces unverified deploys.* Orchestrates: fetch → diff → port → **pause for operator review of the git diff** → commit/push → wait for staging deploy (gh run watch) → verify-fidelity → visual-diff → summary. Safety prompt between every step; operator can stop/redirect without losing loop position. Manual chain remains the documented fallback.

---

## 4. Settings split

**Committed `.claude/settings.json`** (new file — team-shared, safe-public read-only ops):

```json
{
  "permissions": {
    "allow": [
      "Bash(git status*)", "Bash(git diff*)", "Bash(git log*)",
      "Bash(php -l*)",
      "Bash(/Users/*/Library/Application Support/Local/lightning-services/php-*/bin/darwin-arm64/bin/php -l*)",
      "Bash(gh run list*)", "Bash(gh run view*)", "Bash(gh run watch*)",
      "Bash(gh auth status*)",
      "Bash(curl -s https://igniteiqstg.wpenginepowered.com/*)",
      "Bash(curl -s https://igniteiq.com/*)"
    ]
  }
}
```

**Gitignored `.claude/settings.local.json`** (per-operator; written by `/iiq-setup` with explicit confirmation per rule):

```json
{
  "permissions": {
    "allow": [
      "Bash(git add*)", "Bash(git commit*)", "Bash(git pull*)",
      "Bash(git push origin main)", "Bash(git push origin main:*)",
      "Bash(gh workflow run deploy-staging.yml*)",
      "Bash(gh workflow run deploy-production.yml*)",
      "Bash(gh workflow run configure-mail.yml*)",
      "Bash(ssh igniteiqstg@igniteiqstg.ssh.wpengine.net*)",
      "Bash(ssh igniteiq@igniteiq.ssh.wpengine.net*)"
    ],
    "deny": [
      "Bash(git push --force*)", "Bash(git push -f*)",
      "Bash(git reset --hard*)", "Bash(gh secret set*)",
      "Bash(rm -rf*)"
    ]
  }
}
```

Split rationale: read-only ops are safe in a public file; anything irreversible (commits, pushes, deploys, SSH) is opted into per-operator, per-machine — and the denies travel with it. Requires the §parity-plan-11 `.gitignore` fix to land first.

---

## 5. Install mechanism (the one-click)

**New file: repo-root `.claude-plugin/marketplace.json`** — makes this repo its own plugin marketplace (format confirmed from the official marketplace cache):

```json
{
  "name": "ignite",
  "owner": { "name": "IgniteIQ" },
  "plugins": [
    {
      "name": "iiq-team",
      "source": "./iiq-team-plugin",
      "description": "Team-parity toolkit: editor agent + ops ritual skills + bootstrap",
      "category": "productivity"
    },
    {
      "name": "igniteiq-port",
      "source": "./cowork-plugin/igniteiq-port",
      "description": "Design-export port loop (also distributed as a Cowork .plugin zip)",
      "category": "productivity"
    }
  ]
}
```

Operator install (exactly what ONBOARDING.md §2 shows):

```bash
claude plugin marketplace add MLawler-IQ/ignite
claude plugin install iiq-team@ignite
```

Updates: `claude plugin update iiq-team` (pulls the repo tip). Versioning: semver in plugin.json + human-readable `CHANGELOG.md`. The Cowork drag-drop `.plugin` zip remains the distribution path for Cowork sessions — unchanged.

---

## 6. Distribution channel — this repo

**Decision: `/iiq-team-plugin/` lives in this repo**, sibling of `cowork-plugin/`. Rationale: the plugin is meaningless without this codebase (its skills reference repo paths, workflows, OPERATIONS.md); a separate plugins repo adds a second thing to version, sync, and grant access to, for zero benefit at a 2-operator scale. The marketplace.json (§5) makes the same repo serve installs directly.

**Same source, two surfaces:** yes. The 5 port-loop SKILL.md files have one canonical home (`~/.claude/skills/` per current convention) and are synced into BOTH plugin trees by the build script — the existing `build-cowork-plugin.sh` already does exactly this for the Cowork tree. The new team skills (`iiq-*`) are authored directly in `iiq-team-plugin/skills/` (their canonical home) and can be synced into the Cowork tree later if wanted there.

---

## 7. Build script

Extend `scripts/build-cowork-plugin.sh` (165 lines, already: sync-skills → validate-JSON → lint-frontmatter → zip) with a second target:

```bash
bash scripts/build-cowork-plugin.sh             # existing: cowork zip
bash scripts/build-cowork-plugin.sh --iiq-team  # new target
```

`--iiq-team` does: sync the 5 port-loop skills from `~/.claude/skills/` into `iiq-team-plugin/skills/` → validate `plugin.json` + repo-root `marketplace.json` parse → lint every SKILL.md + the agent .md for required frontmatter → **no zip needed** (local installs consume the directory via the marketplace; zip only matters for Cowork). Exit non-zero on any validation failure so it can run as a CI check later.

---

## 8. Implementation checklist (for the build session)

1. `.gitignore` fix + `CODEOWNERS` + branch protection Layer 1 (parity-plan §1/§2/§11) — prerequisites
2. Scaffold `iiq-team-plugin/` + manifest + repo-root `marketplace.json`
3. Author `agents/iiq-web-editor.md` (§2)
4. Author the 9 new skills (§3) — `/iiq-setup` first (it's what every other test depends on), then `/iiq-check-context`, `/iiq-deploy-prod`, then the rest
5. Committed `.claude/settings.json` (§4)
6. Extend build script (§7), run it, fix lint
7. Self-test on Matt's machine: fresh-clone simulation → marketplace add → install → `/iiq-setup` → first-edit walkthrough from ONBOARDING.md §3
8. Then and only then: send Scott the repo invite + ONBOARDING.md
