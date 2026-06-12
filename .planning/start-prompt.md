# Starter prompt for the team-parity planning session

> Paste the block below into a fresh `claude` session inside `~/ignite`. It assumes `OPERATIONS.md` already exists in the repo (it does — written 2026-06-12, imported into `CLAUDE.md` at top).

---

```
Goal: Scott (CEO, non-technical-but-willing) and Matt (technical) should both
be able to drive every website task for igniteiq.com using Claude Code
locally — with identical setup, identical access, and identical context.
Equal trust, no permission tiers between them. The safety net is the repo
(branch protection, deploy gates, audit logs, CODEOWNERS), not differential
access.

Two distinct workflows must be first-class for both operators:

(W1) AD-HOC EDITS — natural-language requests for routine site changes
("change the sign-in link to https://studio.igniteiq.com/#/signin", "remove
the offices from the contact page", "add Marketing Portal to the footer").
This is the daily mode. Handled by the editor agent talking to the codebase,
NOT by a skill per edit type.

(W2) DESIGN-EXPORT PORTING — when a fresh Claude Design handoff URL drops,
the operator runs the canonical port loop: /fetch-iiq-design → /diff-iiq-
export → /port-iiq-diff → human review + commit/push → GitHub Action deploys
to staging → /verify-iiq-fidelity → /visual-iiq-diff. This sequence already
exists in the codebase (CLAUDE.md "The end-to-end loop") and is implemented
by 5 existing skills in cowork-plugin/igniteiq-port/. It must remain
first-class, bundled in the new plugin, and explicitly documented in the
agent's system prompt as the preferred path when an export drops.

DON'T BUILD ANYTHING YET. Produce the three deliverables described in D2/D3/
D4 as written planning documents. Stop after writing them.

═══════════════════════════════════════════════════════════════════════
HARD REQUIREMENTS the plan must address
═══════════════════════════════════════════════════════════════════════

(R1) CONTEXT PARITY. OPERATIONS.md already captures the live-site wiring
(deploys, email, DNS, secrets, gotchas) and auto-loads via @OPERATIONS.md in
CLAUDE.md. The plan must NOT duplicate OPERATIONS.md content — instead, it
must propose:
  - How OPERATIONS.md stays current as we wire more things (who edits it,
    when, what triggers a review).
  - What additional repo-resident docs are needed to round out the picture
    (AGENTS.md for deeper detail? CONTRIBUTING.md for the workflow?).

(R2) CONCURRENT EDITS. Both operators may make changes simultaneously. For
each design choice in D3 and D4, describe what happens when:
  (a) Both push to main within minutes of each other.
  (b) One operator is mid-deploy while the other pushes.
  (c) Scott edits a Site Settings field in WP admin while a deploy is
      running `wp igniteiq seed --force` 10 min later. (Hint: depends on
      whether the field is inside a seeded cornerstone's page_sections or a
      Site Settings option. OPERATIONS.md covers which is which.)
  (d) The agent on Scott's machine is asked to edit a file that Matt has
      uncommitted local changes in (different machines, no shared local
      state — what does it see / not see?).
Show failure mode AND mitigation for each.

(R3) HARD GUARDRAILS. Equal trust does NOT mean equal yolo. The safety net
must catch mistakes from either operator. Design:
  - Hard denies in the agent — no rm, no force-push, no edits to
    wp-content/mu-plugins/, no edits to .github/workflows/, no DB writes
    outside ACF, no edits to repo secrets, no DNS changes via raw API
    without a documented `/iiq-publish-dns` ritual.
  - Branch protection on main — required status checks? CODEOWNERS for
    sensitive paths (/pricing/, /privacy/, /terms/, /signin/, homepage
    hero, SEO-sensitive title/meta/H1/slug, mail/DNS code, workflows)?
  - Audit trail — git blame covers code; for ACF/post edits in WP admin,
    propose installing Stream or Simple History so user+field+timestamp is
    logged.
  - Secret hygiene — both operators have read access to GH Actions
    secrets (necessary). Propose a rotation cadence (every N months) and
    a "rotate after chat exposure" rule.
  - Rollback paths — code: `git revert` + redeploy. ACF Site Settings:
    NO built-in revision history; last save wins. Propose ONE concrete
    solution (revision plugin OR nightly wp_options snapshot via WPE
    backups OR ACF Local JSON committed on save) and justify.

(R4) CONTENT BOUNDARIES. Not all edits are equal:
  - Voice enforcement — IgniteIQ voice is bold/direct/ownership-focused,
    short declarative, active voice, no filler (per CLAUDE.md). Codify in
    the agent's system prompt with positive + negative examples drawn from
    existing copy in inc/cli.php and template-parts/.
  - SEO-sensitive fields — title tags, meta descriptions, H1s, canonical
    URLs, slugs, internal anchor text carry rank impact. Either lock these
    out of casual editability OR require an "SEO-REVIEW" marker in commit/
    PR. Propose enforcement (CODEOWNERS on slug/meta-affecting files?
    pre-commit lint?).
  - Sensitive paths — /pricing/, /privacy/, /terms/, /signin/, homepage
    hero require review even when direct-push is the default. CODEOWNERS
    is the lever.
  - Re-seed clobber — `wp igniteiq seed --force` overwrites cornerstone
    page_sections every deploy. Choose: (a) change `--force` to
    seed-if-empty (safer but breaks "deploy reflects code"), (b) keep
    editable content in Site Settings options only (current direction),
    (c) accept the loss and document the rule. Justify.
  - Cross-env drift — once content lives in WP admin, staging and prod
    wp_options diverge silently. Propose detection (nightly wp option diff
    + Slack alert, or a /iiq-env-diff skill).

═══════════════════════════════════════════════════════════════════════
DELIVERABLES
═══════════════════════════════════════════════════════════════════════

(D2) ONBOARDING.md at repo root — Scott's setup checklist, copy-pasteable.
Step-by-step from "Mac with nothing installed" to "first successful staging
deploy from Scott's laptop":

  Section 1 — Account prerequisites (irreducibly manual; Scott does once):
    * Anthropic account + Claude Code login (separate seat, not shared)
    * GitHub: accept repo invite to MLawler-IQ/ignite, install gh CLI,
      `gh auth login` with workflow scope
    * SSH: generate Ed25519 keypair on his Mac, upload pubkey via the WPE
      User Portal under his name (audit trail attached), copy the
      `~/.ssh/config` snippet from OPERATIONS.md
    * Google Workspace: confirm super-admin access (he already has it)
    * Cloudflare: get added to the igniteiq.com account as admin so he
      can create his own DNS token if needed
    * Local: install Node + Playwright + Chromium for /iiq-visual-diff:
        `npx playwright install chromium` from the repo root after
        `npm install`

  Section 2 — One-click Claude Code setup via plugin (D4):
    * `gh repo clone MLawler-IQ/ignite && cd ignite`
    * Install the iiq-team plugin (mechanism per D4; document both manual
      and CLI install)
    * Copy the .claude/settings.local.json starter from a documented code
      block in this file (since .local.json is gitignored)
    * Run `claude` → confirm OPERATIONS.md auto-loaded by asking the agent
      to summarize the live wiring

  Section 3 — First-edit walkthrough (replay an actual recent edit):
    Use the contact-page aside edit from 2026-06-12 (commit 9b50a47):
    "On the contact page, remove the offices and the direct email/phone,
    replace with 'Headquartered in San Diego, CA.'" Show Scott the agent
    invocation, the diff it produces, the lint step, the commit, the push,
    the staging deploy watch, the /iiq-verify-fidelity + /iiq-visual-diff
    checks, and the production deploy gate. End-to-end, including what
    success and failure look like.

  Section 4 — When stuck:
    * Quick reference to the workflow skills (one line each)
    * Where to find OPERATIONS.md
    * How to ping Matt without blocking (Slack channel? GH issue?)

  Target: <350 lines. Concrete commands and clickpaths only.

═══════════════════════════════════════════════════════════════════════

(D3) PARITY PLAN at .planning/parity-plan.md.
The concurrency + safety conventions when both operators are full
committers. Sections:

  1. Branch policy — direct-push to main for routine edits; branch + PR
     for the sensitive-paths list (R4). Concrete CODEOWNERS file proposal.

  2. GitHub branch protection settings — exact checkbox-by-checkbox
     proposal (required reviews for CODEOWNERS paths, required status
     checks if any, no force-push, no deletes).

  3. Pull-rebase-before-push convention. What the agent does on conflict
     (abort, re-read, retry; never force-push, never `git reset --hard`).

  4. Audit trail solution — WP plugin choice (Stream vs Simple History),
     justify, install steps, retention.

  5. ACF Site Settings revision history solution — pick ONE (revision
     plugin OR nightly wp_options snapshot OR ACF Local JSON committed
     on save). Justify and sketch implementation.

  6. Deploy notifications — Slack incoming webhook in deploy-staging.yml
     and deploy-production.yml (success + failure). Channel name proposal.

  7. Cross-env drift detection — design /iiq-env-diff that diffs prod
     wp_options against staging and reports. Run nightly via cron or
     scheduled GH Action; post to Slack on drift.

  8. Conformance matrix — for each of R1/R2/R3/R4, a table showing how
     the policies above handle the scenario, OR an explicit "fails open
     because X" with a follow-up item.

  Target: <500 lines.

═══════════════════════════════════════════════════════════════════════

(D4) PLUGIN SPEC at .planning/iiq-team-plugin-spec.md.
Design the `iiq-team` plugin for one-click parity. Use
cowork-plugin/igniteiq-port/ as the structural template. The plugin's core
value is the AGENT, not a long skill list — Scott uses natural language for
content edits (W1); the agent does the work informed by auto-loaded
CLAUDE.md + OPERATIONS.md.

  Spec the following:

  1. plugin.json manifest (name, version, description, install hooks)

  2. ONE agent definition: agents/iiq-web-editor.md.
     System prompt must cover:
       * Voice rules (bold/direct/ownership-focused; short declarative;
         active voice; no filler). 2-3 positive examples + 2-3 negative
         examples drawn from existing IgniteIQ copy in inc/cli.php /
         template-parts.
       * Fidelity protocol — when to mark a FIDELITY EXCEPTION, exact
         comment format, examples from current code (nav.php signin
         retarget, contact.php aside collapse).
       * PHP-lint discipline — always run the Local-shipped php -l before
         commit (path in CLAUDE.md).
       * Pull-rebase-before-push, never force-push; abort on conflict.
       * Direct-push vs branch policy from D3 — direct for routine edits,
         branch + PR for sensitive paths (cross-ref CODEOWNERS).
       * Commit message style — concise subject (≤72 chars), body
         explains why; reference `git log -10` as the style guide.
       * Cache awareness — Cloudflare edge can serve stale up to 10 min
         after deploy; tell the user when this matters.
       * **Workflow recognition** — when an operator pastes a Claude
         Design handoff URL or says "match the live site to this export",
         route into W2 (the port loop), NOT into ad-hoc edits.

     Tool restrictions:
       * Read/Edit ALLOWED: igniteiq/inc/*, igniteiq/template-parts/**,
         igniteiq/assets/css/*, igniteiq/assets/js/* (excluding minified
         bundles), igniteiq/page-*.php, OPERATIONS.md, CLAUDE.md,
         .planning/**.
       * Read/Edit DENIED: wp-content/mu-plugins/**, .github/workflows/**,
         anything containing secrets or DKIM/Cloudflare credentials,
         .claude/settings.json (team-shared — manual edits only).
       * Bash ALLOWED: git status/diff/add/commit/log/pull/push (origin
         main only), php -l, gh run list/view/watch, gh workflow run for
         the three known workflows, curl read-only.
       * Bash DENIED: rm, git push --force, git reset --hard, anything
         modifying secrets or DNS without going through a skill.

  3. SHORT, INTENTIONAL list of workflow skills. Skills encode RITUALS
     that are easy to get wrong — not content categories:

     Deploy + verification rituals (post-edit canonical sequence):
       * /iiq-deploy-prod — backup check, cornerstone hand-edit check,
         gate, watch, verify URLs, CF purge prompt
       * /iiq-verify-fidelity — wraps the existing /verify-iiq-fidelity
         skill (export string check on rendered HTML)
       * /iiq-visual-diff — wraps the existing /visual-iiq-diff skill
         (Playwright screenshots of all 6 pages, export vs staging vs
         prod, per-page gap report). REQUIRED after any visible change.
         Mac-local only — depends on Chromium + Playwright (handled by
         ONBOARDING.md section 1).

     Pre-flight + state inspection:
       * /iiq-check-context — reads OPERATIONS.md + verifies env state
         (gh authed, SSH reachable, secrets present, working tree clean).
         Run at session start.
       * /iiq-env-diff — diff prod vs staging wp_options to catch drift
         (cross-ref D3.7).

     Mail + DNS rituals:
       * /iiq-verify-mail — submits a test contact form to a given env,
         checks delivery (Gmail MCP if present, else logs marker for
         manual check)
       * /iiq-rotate-smtp-pass — guided multi-step for app password
         rotation
       * /iiq-publish-dns — opinionated wrapper around the Cloudflare API
         for documented DNS changes (SPF, DKIM, DMARC, simple records).
         Refuses to touch MX without a confirmation prompt.
       * /iiq-purge-cf-cache — per-URL Cloudflare purge with the right
         zone ID

     Design-export port loop (W2) — BUNDLE the existing 5 skills from
     cowork-plugin/igniteiq-port/ unchanged:
       * /fetch-iiq-design  (handoff URL → exports/<date>/)
       * /diff-iiq-export   (DIFF.md against staging)
       * /port-iiq-diff     (edit cli.php + template-parts byte-accurately)
       * /verify-iiq-fidelity (export string check)
       * /visual-iiq-diff   (already promoted above as /iiq-visual-diff;
                            keep the original name as a callable alias
                            for backwards compat)

     Convenience orchestrator:
       * /iiq-port-design <handoff-url> — runs the full W2 chain
         (fetch → diff → port → verify → visual) with safety prompts
         between steps so the operator can intervene without losing
         context. Optional; document the manual chain as the fallback.

     Explicitly justify every skill — each must encode a RITUAL that's
     easy to get wrong (multi-step ordering, secret handling, irreversibility),
     not a content category. Ad-hoc content edits go through the agent in W1.

  4. Starter settings split:
     * Team-shared `.claude/settings.json` (committed, in git): permission
       allowlist for read-only ops (gh run list/view/watch, git status/
       diff/log, php -l, curl read patterns). Safe to share publicly.
     * Per-operator `.claude/settings.local.json` (gitignored): irreversible
       ops (git push origin main, git commit, gh workflow run for the three
       deploy workflows). Each operator opts in explicitly.
     Decide each rule based on whether it's safe public knowledge.

  5. Install mechanism — `claude plugin install <git-url-or-path>`? Manual
     drag-drop of a built .plugin file? Both? Versioning (semver, changelog
     file in plugin root). Update strategy (pinned vs auto-update).

  6. Distribution channel — keep in this repo at /iiq-team-plugin/ alongside
     cowork-plugin/igniteiq-port/, OR a separate plugins repo? Justify.
     Bonus: can the same plugin source produce both a Cowork .plugin and an
     installable Claude Code plugin? Same skill files, two surfaces.

  7. Build script — extend or mirror scripts/build-cowork-plugin.sh.

  Target: <600 lines.

═══════════════════════════════════════════════════════════════════════
CONTEXT TO PULL IN BEFORE PLANNING
═══════════════════════════════════════════════════════════════════════

* CLAUDE.md, OPERATIONS.md (both auto-loaded, you already have them)
* AGENTS.md if present
* .github/workflows/configure-mail.yml, deploy-staging.yml, deploy-
  production.yml — end-to-end, including `concurrency:` blocks
* git log --oneline -30; diff the last 5 non-deploy commits (recent edits
  are the prototype for what Scott needs to do)
* igniteiq/inc/contact-form.php, mail.php, cli.php, acf-field-groups.php,
  acf-options-page.php
* igniteiq/template-parts/nav.php, footer.php, forms/contact.php (recently
  edited — see how FIDELITY EXCEPTION comments are styled)
* .claude/settings.local.json (current permission state)
* cowork-plugin/igniteiq-port/ — existing plugin structure to mirror for
  D4. Especially: how the existing 5 design-loop skills are organized,
  the manifest format, build script in scripts/build-cowork-plugin.sh
* For each existing port-loop skill, read its SKILL.md at
  ~/.claude/skills/<name>/SKILL.md (per CLAUDE.md, those are canonical
  sources; the plugin bundle mirrors them)

═══════════════════════════════════════════════════════════════════════
OUTPUT FORMAT
═══════════════════════════════════════════════════════════════════════

Three files written to disk:
  - ~/ignite/ONBOARDING.md (D2, complete and copy-pasteable)
  - ~/ignite/.planning/parity-plan.md (D3, planned)
  - ~/ignite/.planning/iiq-team-plugin-spec.md (D4, planned, no working code)

Stop after writing the three files. Do not start building the plugin —
that's a separate session after I review the spec.
```

---

## Notes for the operator who runs this prompt

- OPERATIONS.md is already in place — don't let the planner duplicate it. It can extend or reference, not re-derive.
- The Anthropic + GH + WPE accounts in D2 section 1 must be Scott's own — credential-sharing is off the table (TOS + audit + 2FA reasons).
- The plugin spec (D4) is design-only — actual implementation happens in a follow-up session. Don't merge into main until reviewed.
- After the three files exist, decide direction phase-by-phase:
  1. Apply D3 (branch protection, CODEOWNERS, audit plugin) — low-risk infra.
  2. Walk Scott through D2 onboarding manually the first time before any plugin exists.
  3. Build the plugin per D4 in a third session.
