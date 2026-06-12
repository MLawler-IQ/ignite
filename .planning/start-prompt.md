# Starter prompt — team-parity planning session

> Paste the fenced block below into a fresh `claude` session inside `~/ignite`. The session will produce three planning documents; no code will be built.

> **Already in place when this runs:**
> - `OPERATIONS.md` at repo root (live-site wiring) — auto-loads via `@OPERATIONS.md` in `CLAUDE.md`
> - `cowork-plugin/igniteiq-port/` with the 5 existing design-loop skills (`/fetch-iiq-design`, `/diff-iiq-export`, `/port-iiq-diff`, `/verify-iiq-fidelity`, `/visual-iiq-diff`) and build script at `scripts/build-cowork-plugin.sh`
> - Contact form email path live on prod (Workspace SMTP + DKIM/SPF/DMARC passing)
> - Site Settings ACF `contact_email` seeded on both envs

---

```
Goal: Scott (CEO, non-technical-but-willing) and Matt (technical) should both
be able to drive every website task for igniteiq.com using LOCAL Claude Code
with identical setup, identical access, and identical context. Equal trust,
no permission tiers between them. The safety net is the repo (branch
protection, deploy gates, audit logs, CODEOWNERS), not differential access.

Architecture decision already made: local Claude Code CLI on each operator's
Mac, configured by a single one-click `iiq-team` plugin install. The plugin
self-bootstraps via a `/iiq-setup` skill that handles every dependency
except the irreducible human steps (Anthropic login, gh auth, generating an
SSH keypair, accepting the repo invite). We are NOT building a custom web
portal. claude.ai/code (web) is a known fallback if local install ever
blocks, but the plan should optimize for the local-CLI path.

Two distinct workflows must be first-class for both operators:

(W1) AD-HOC EDITS — natural-language requests for routine site changes
("retarget the sign-in link to studio.igniteiq.com", "drop the offices
block from the contact page", "add Marketing Portal under Resources in the
footer"). This is daily mode. Handled by the editor agent talking to the
codebase informed by auto-loaded CLAUDE.md + OPERATIONS.md, NOT by a skill
per edit type. Skill cardinality cannot keep up with edit variety; the
agent's intelligence does.

(W2) DESIGN-EXPORT PORTING — when a fresh Claude Design handoff URL drops,
the operator runs the existing port loop: /fetch-iiq-design →
/diff-iiq-export → /port-iiq-diff → human review + commit/push → GitHub
Action deploys to staging → /verify-iiq-fidelity → /visual-iiq-diff. This
sequence is documented in CLAUDE.md ("The end-to-end loop") and implemented
by the 5 existing skills in cowork-plugin/igniteiq-port/. It must remain
first-class, bundled in the new plugin unchanged, and the editor agent's
system prompt must recognize a handoff URL or "match the site to this
export" phrasing and route into W2 instead of treating the request as W1.

DON'T BUILD ANYTHING YET. Produce the three deliverables described below
as written planning documents. Stop after writing them.

═══════════════════════════════════════════════════════════════════════
HARD REQUIREMENTS the plan must address
═══════════════════════════════════════════════════════════════════════

(R1) CONTEXT PARITY. OPERATIONS.md already captures the live-site wiring
and auto-loads via CLAUDE.md's @OPERATIONS.md import. Don't duplicate it.
Do propose:
  - Maintenance protocol — who edits OPERATIONS.md when new infra lands;
    a checklist of "did we update OPERATIONS.md?" attached to the deploy
    ritual.
  - Whether AGENTS.md (deeper manual) or CONTRIBUTING.md should be added
    to round out the picture. Keep additions minimal — three documents
    max for shared context.

(R2) CONCURRENT EDITS. Both operators may make changes simultaneously. For
each design choice in D3 and D4, describe what happens when:
  (a) Both push to main within minutes of each other.
  (b) One operator is mid-deploy while the other pushes.
  (c) Scott edits a Site Settings field in WP admin while a deploy runs
      `wp igniteiq seed --force` 10 min later. (Cite OPERATIONS.md for
      which fields seed --force touches vs. leaves alone.)
  (d) The agent on Scott's machine is asked to edit a file Matt has
      uncommitted local changes in (separate machines — what does Scott's
      agent see vs not see).
Show failure mode AND mitigation for each.

(R3) HARD GUARDRAILS. Equal trust does NOT mean equal yolo. Design:
  - Agent hard denies — no rm, no force-push, no edits to
    wp-content/mu-plugins/**, no edits to .github/workflows/**, no DB
    writes outside ACF, no edits to repo secrets, no DNS via raw API
    without going through a documented `/iiq-publish-dns` skill.
  - Branch protection on main — required status checks (if any?),
    CODEOWNERS file requiring Matt's review on sensitive paths
    (/pricing/, /privacy/, /terms/, /signin/ page templates, homepage
    hero seed, SEO-sensitive title/meta/H1/slug fields, anything in
    inc/mail.php or inc/contact-form.php, .github/workflows/**). No
    force-push, no deletes.
  - Audit trail — git blame for code; for ACF/post edits in WP admin,
    propose installing Stream OR Simple History with retention policy.
  - Secret hygiene — rotation cadence for IIQ_SMTP_PASS, WPE_SSH_KEY,
    Cloudflare token (every N months); "rotate after chat exposure"
    rule for any credential ever shared in a chat window.
  - Rollback paths — code: `git revert` + redeploy (well understood).
    ACF Site Settings: no built-in revision history; pick ONE solution
    (revision plugin OR nightly wp_options snapshot via WPE backups OR
    ACF Local JSON committed on save) and justify.

(R4) CONTENT BOUNDARIES. Not all edits are equal:
  - Voice enforcement — IgniteIQ voice is bold/direct/ownership-focused;
    short declarative; active voice; no filler (CLAUDE.md). Codify in
    the editor agent's system prompt with 2-3 positive + 2-3 negative
    examples drawn from existing copy in inc/cli.php and template-parts.
  - SEO-sensitive fields — title tags, meta descriptions, H1s, canonical
    URLs, slugs, internal anchor text carry rank impact. Either lock
    these out of casual editability OR require an "SEO-REVIEW" marker
    in commit. Pick a mechanism (CODEOWNERS on the relevant files? a
    pre-commit lint? a skill that warns?) and justify.
  - Sensitive paths — covered by CODEOWNERS in R3.
  - Re-seed clobber — `wp igniteiq seed --force` overwrites cornerstone
    page_sections every deploy. Choose: (a) flip to seed-if-empty,
    (b) keep editable content in Site Settings options only (current
    direction), (c) accept and document. Justify.
  - Cross-env drift — propose a /iiq-env-diff skill that compares
    relevant wp_options between staging and prod and reports drift.

═══════════════════════════════════════════════════════════════════════
DELIVERABLES
═══════════════════════════════════════════════════════════════════════

(D2) ONBOARDING.md at repo root — Scott's setup checklist, intentionally
SHORT. Most of the legwork has been pushed into the plugin's /iiq-setup
skill (see D4). Sections:

  1. Irreducibly manual (Scott does once, ~10 min total):
     * Install Claude Code (single .dmg from Anthropic)
     * Install gh CLI (`brew install gh`)
     * `gh auth login` — browser-based, choose workflow scope
     * Generate Ed25519 SSH keypair (`ssh-keygen -t ed25519`)
     * Upload pubkey via WPE User Portal under his name (audit trail)
     * Accept the GitHub repo invite (sent separately)
     * Confirm Anthropic Claude Code seat (Matt provisions)
     * Confirm Google Workspace + Cloudflare access (already has)

  2. One-click Claude Code parity:
     * `gh repo clone MLawler-IQ/ignite && cd ignite`
     * Install the iiq-team plugin (exact command per D4)
     * Run `/iiq-setup` — the plugin handles npm install,
       Playwright/Chromium download, ~/.ssh/config wiring, gh auth
       verification, env reachability check, OPERATIONS.md context
       summary. Idempotent — safe to re-run.

  3. First-edit walkthrough (replay a real recent edit end-to-end):
     Use commit 9b50a47 (the contact-page aside collapse + footer Sign
     In + Marketing Portal additions) — same prompt the operator typed,
     same diff produced, same lint/commit/push/staging-deploy-watch/
     verify-fidelity/visual-diff/prod-deploy-gate sequence. Show what
     success looks like AND what a common failure (cache stale on prod)
     looks like and how to handle it.

  4. When stuck:
     * One-line reference to each workflow skill
     * Pointer to OPERATIONS.md sections by topic
     * How to get unblocked without pinging Matt (and when to ping anyway)

  Target: <300 lines.

═══════════════════════════════════════════════════════════════════════

(D3) PARITY PLAN at .planning/parity-plan.md.
Concurrency + safety conventions when both operators are full committers:

  1. Branch policy — direct-push to main for routine edits; branch + PR
     for the sensitive-paths list (R4). Concrete CODEOWNERS file proposal.

  2. GitHub branch protection settings — checkbox-by-checkbox proposal
     (required review for CODEOWNERS paths, no force-push, no deletes).

  3. Pull-rebase-before-push convention. What the agent does on conflict
     (abort, re-read, retry; never force-push, never `git reset --hard`).

  4. WP admin audit-trail plugin — Stream vs Simple History pick + install.

  5. ACF Site Settings revision history — pick ONE (revision plugin,
     nightly wp_options snapshot, or ACF Local JSON committed on save).

  6. Deploy notifications — Slack incoming webhook in deploy-staging.yml
     + deploy-production.yml, success + failure channels.

  7. Cross-env drift detection — /iiq-env-diff design + cadence
     (on-demand and nightly).

  8. Secret rotation cadence — every N months for IIQ_SMTP_PASS,
     WPE_SSH_KEY, Cloudflare token; documented "rotate after chat
     exposure" rule.

  9. Conformance matrix — R1/R2/R3/R4 vs. the policies above. Each cell
     is "handled by X" or "fails open because Y, follow-up item".

  Target: <500 lines.

═══════════════════════════════════════════════════════════════════════

(D4) PLUGIN SPEC at .planning/iiq-team-plugin-spec.md.
Design the `iiq-team` plugin for local Claude Code parity. Use
cowork-plugin/igniteiq-port/ as the structural template — same manifest
shape, mirrored build pipeline. Plugin's core value is the AGENT plus
ritual skills, NOT a long content-edit skill list.

  1. plugin.json manifest (name, version, description, install hooks).

  2. ONE agent definition: agents/iiq-web-editor.md.
     System prompt must cover:
       * Voice rules (bold/direct/ownership-focused; short declarative;
         active voice; no filler). 2-3 positive + 2-3 negative examples
         drawn from existing copy in inc/cli.php and template-parts.
       * Fidelity protocol — when to mark FIDELITY EXCEPTION, exact
         comment format, examples (nav.php signin retarget, contact.php
         aside collapse — both in current code).
       * PHP-lint discipline — always `php -l` (Local-shipped 8.2 binary;
         path in CLAUDE.md) before commit.
       * Pull-rebase-before-push, never force-push; abort on conflict.
       * Direct-push vs branch policy from D3.
       * Commit message style — concise subject ≤72 chars, body explains
         why; reference `git log -10` for tone.
       * Cache awareness — Cloudflare edge can serve stale up to 10 min
         after deploy; surface this to the operator.
       * WORKFLOW RECOGNITION — when input includes a Claude Design
         handoff URL OR phrasing like "match the site to this export"
         OR "port the latest design", route into W2 (run /iiq-port-design
         or step through the port loop), NOT W1.

     Tool restrictions:
       * Read/Edit ALLOWED: igniteiq/inc/*, igniteiq/template-parts/**,
         igniteiq/assets/css/*, igniteiq/assets/js/* (excluding minified
         bundles), igniteiq/page-*.php, OPERATIONS.md, CLAUDE.md,
         .planning/**.
       * Read/Edit DENIED: wp-content/mu-plugins/**, .github/workflows/**,
         anything containing IIQ_SMTP_* or DKIM/Cloudflare credentials,
         .claude/settings.json (team-shared — manual review only).
       * Bash ALLOWED: git status/diff/add/commit/log/pull, git push
         origin main, php -l, gh run list/view/watch, gh workflow run
         for the three known workflows (deploy-staging.yml,
         deploy-production.yml, configure-mail.yml), curl read-only.
       * Bash DENIED: rm, git push --force, git reset --hard, gh secret
         set, anything touching DNS without going through /iiq-publish-dns.

  3. SHORT, INTENTIONAL skill list. Every skill encodes a RITUAL easy to
     get wrong (multi-step ordering, secret handling, irreversibility) —
     never a content category.

     Bootstrap:
       * /iiq-setup — one-time post-install bootstrap. Steps: detect
         repo root, run `npm install`, `npx playwright install chromium`,
         write ~/.ssh/config WPE host entries if missing (asks first),
         verify `gh auth status` (prompts to login if not authed),
         test SSH no-op to both WPE envs, run /iiq-check-context to
         confirm OPERATIONS.md auto-loaded. Idempotent. Only manual
         prerequisite left for the operator: `brew install node` (one
         time — needs sudo, can't be automated).

     Deploy + verification rituals:
       * /iiq-deploy-prod — pre-flight (cornerstone hand-edit check,
         backup reminder), dispatch deploy-production.yml, watch run,
         verify URLs, prompt for /iiq-purge-cf-cache + /iiq-verify-
         fidelity + /iiq-visual-diff.
       * /iiq-verify-fidelity — wraps the existing /verify-iiq-fidelity
         skill (export-string check on rendered HTML).
       * /iiq-visual-diff — wraps the existing /visual-iiq-diff skill
         (Playwright screenshots across 6 cornerstones, export vs
         staging vs prod, per-page gap report). REQUIRED after any
         visible change. Mac-local only (needs Chromium — installed by
         /iiq-setup).

     Pre-flight + state inspection:
       * /iiq-check-context — reads OPERATIONS.md, verifies env state
         (gh authed, SSH reachable, secrets present, working tree
         clean). Run at session start.
       * /iiq-env-diff — diffs prod vs staging wp_options for the keys
         the team manages (contact_email, footer_columns, etc.) and
         reports drift. On-demand + nightly cron via GH Action.

     Mail + DNS rituals:
       * /iiq-verify-mail — submits a test contact form to a given env,
         checks delivery (Gmail MCP if present, else logs marker for
         manual check).
       * /iiq-rotate-smtp-pass — guided multi-step for app password
         rotation (Google → GH secret → re-run configure-mail.yml ×2).
       * /iiq-publish-dns — opinionated wrapper around the Cloudflare
         API for documented record changes (SPF/DKIM/DMARC + simple
         A/CNAME/TXT). Refuses to touch MX without a confirmation
         prompt.
       * /iiq-purge-cf-cache — per-URL Cloudflare purge with the right
         zone ID.

     Design-export port loop (W2) — BUNDLE the existing 5 skills from
     cowork-plugin/igniteiq-port/ unchanged:
       * /fetch-iiq-design   (handoff URL → exports/<date>/)
       * /diff-iiq-export    (DIFF.md against staging)
       * /port-iiq-diff      (byte-accurate edits to cli.php +
                              template-parts)
       * /verify-iiq-fidelity (re-exposed under /iiq-verify-fidelity
                              above; keep original name as alias)
       * /visual-iiq-diff    (re-exposed under /iiq-visual-diff above;
                              keep original name as alias)

     Convenience orchestrator:
       * /iiq-port-design <handoff-url> — runs the full W2 chain (fetch
         → diff → port → human-review pause → commit/push → wait for
         staging deploy → verify-fidelity → visual-diff) with safety
         prompts between steps so the operator can intervene without
         losing context. Manual chain remains the fallback.

     Explicitly justify every skill — each must encode a ritual easy to
     get wrong. Ad-hoc content edits flow through the agent in W1; they
     do not get their own skills.

  4. Starter settings split:
     * Team-shared `.claude/settings.json` (committed): permission
       allowlist for read-only ops (gh run list/view/watch, git
       status/diff/log, php -l, curl read patterns). Safe public.
     * Per-operator `.claude/settings.local.json` (gitignored): the
       irreversible ops (git commit, git push origin main, gh workflow
       run for the three deploy workflows). Each operator opts in
       explicitly via /iiq-setup writing a starter file from a documented
       template.

  5. Install mechanism — `claude plugin install <git-url-or-path>` and
     manual drag-drop of a built `.plugin` file. Versioning (semver +
     CHANGELOG.md in plugin root). Update strategy (operator runs
     `claude plugin update iiq-team` on cadence; plugin's first run
     each session can detect available updates).

  6. Distribution channel — keep in this repo at /iiq-team-plugin/
     alongside cowork-plugin/igniteiq-port/. Justify staying in one
     repo (single source of truth, plugin evolves with the codebase).
     Bonus: can the same source produce a Cowork .plugin AND a local
     Claude Code plugin? Same skill files, two surfaces.

  7. Build script — extend or mirror scripts/build-cowork-plugin.sh.
     Show the new build target.

  Target: <600 lines.

═══════════════════════════════════════════════════════════════════════
CONTEXT TO PULL IN BEFORE PLANNING
═══════════════════════════════════════════════════════════════════════

* CLAUDE.md and OPERATIONS.md (auto-loaded; you already have them)
* AGENTS.md if present
* .github/workflows/{configure-mail,deploy-staging,deploy-production}.yml
  end-to-end, including `concurrency:` blocks
* git log --oneline -30; diff the 5 most recent non-deploy commits —
  these ARE the prototype for what the editor agent will be asked to do
* igniteiq/inc/{contact-form,mail,cli,acf-field-groups,acf-options-page}.php
* igniteiq/template-parts/{nav,footer,forms/contact}.php — recent edits
  show how FIDELITY EXCEPTION comments are styled
* .claude/settings.local.json (current operator permissions baseline)
* cowork-plugin/igniteiq-port/ — STRUCTURAL TEMPLATE for D4. Read the
  manifest format, skill organization, and scripts/build-cowork-plugin.sh
* For each existing port-loop skill, read its SKILL.md at
  ~/.claude/skills/<name>/SKILL.md — the plugin bundle mirrors those

═══════════════════════════════════════════════════════════════════════
OUTPUT FORMAT
═══════════════════════════════════════════════════════════════════════

Three files written to disk:
  - ~/ignite/ONBOARDING.md
  - ~/ignite/.planning/parity-plan.md
  - ~/ignite/.planning/iiq-team-plugin-spec.md

Stop after writing the three files. Do NOT start building the plugin —
that's a separate session after the spec is reviewed.
```

---

## Operator notes (read before running the prompt)

- **Don't let the planner re-derive OPERATIONS.md** — it's auto-loaded, treat as canon.
- **Scott's GitHub invite** — separate one-time task (`gh api -X PUT /repos/MLawler-IQ/ignite/collaborators/<scott-gh-username> -f permission=push`). Get Scott's GH handle before sending.
- **Plugin build is a follow-up session** — after the spec is reviewed, a fresh session implements per `iiq-team-plugin-spec.md`. Don't start building in the same session that produces the spec.
- **Phasing** when the deliverables come back:
  1. Apply D3 first — branch protection, CODEOWNERS, audit-trail plugin, deploy notifications. Low-risk infra, immediate value.
  2. Walk Scott through D2 onboarding manually the first time, with the plugin already built per D4.
  3. Iterate on the plugin in follow-up sessions as edge cases surface.
