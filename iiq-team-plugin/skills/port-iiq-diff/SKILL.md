---
name: port-iiq-diff
description: Port the latest Claude Design export into the IgniteIQ WordPress theme by editing seed rows and template-parts byte-accurately. Use when the user says "port the diff", "port iiq", or invokes "/port-iiq-diff".
---

# port-iiq-diff

## What this skill does

Reads `~/ignite/exports/latest/DIFF.md` (the porting backlog the watcher pre-generated) plus the export's HTML/JS for context, then edits `igniteiq/inc/cli.php` and `igniteiq/template-parts/*.php` to land every "missing on staging" string. Runs `php -l` on every modified file. Prints a summary with `git diff` stats. **Does NOT run git, does NOT touch staging, does NOT auto-commit.** The user reviews the diff in their editor, runs `bash deploy.sh` for a Local visual check, then commits and pushes when satisfied.

## Inputs (already on disk by the time this skill runs)

- `~/ignite/exports/latest/DIFF.md` — porting backlog with two sections:
  - "Missing on staging — porting backlog" (checklist of strings to add)
  - "Extra on staging — review" (strings to remove or ignore)
- `~/ignite/exports/latest/{*.html,js/*.js}` — the export source
  (content root may be flat, in `site/`, or `igniteiq-website/project/` — auto-detect with `find -maxdepth 4 -name index.html`)
- `~/ignite/igniteiq/inc/cli.php` — current `IgniteIQ_CLI::default_pages()` seed
- `~/ignite/igniteiq/template-parts/{heroes,sections,diagrams,forms}/*.php` — current renderers
- `~/ignite/igniteiq/page-*.php` and `front-page.php` — page templates
- `~/ignite/igniteiq/assets/js/contact-form.js` — contact form JS

## Algorithm

1. **Load the backlog.** Read `exports/latest/DIFF.md`. Parse the "Missing on staging" checklist (lines beginning `- [ ] `) and the "Extra on staging" list. If the file is missing, stop and tell the user: "No DIFF.md found at exports/latest/. Drop a Claude Design export into the cloud inbox and let the watcher process it, or run /diff-iiq-export manually."

2. **Locate each missing string in the export source.** For each missing string, grep the export's HTML and JS bundle to identify which page it belongs to (home, how-it-works, ontology, company, contact, signin). Use the export's content root (auto-detect via `find -maxdepth 4 -name index.html`).

3. **Map each string to a target.** Either:
   - A field in a `cli.php` `default_pages()['<slug>'][n]` seed row (most user-visible copy lives here), or
   - A hardcoded string in a `template-parts/{heroes,sections,diagrams,forms}/<name>.php` file (rare — most strings flow through `default_pages()`).
   - For the contact form, also consider `igniteiq/assets/js/contact-form.js` and `igniteiq/inc/contact-form.php`.

4. **Apply edits byte-accurately.** Never paraphrase user-facing copy. Preserve em-dashes (`—`), middots (`·`), curly quotes (`'` `'` `"` `"`), bracketed sublabels, and exact capitalization. Use the `Edit` tool. Mirror seed-row edits and template-part edits in lockstep — markup-only edits leave the DB stale; seed-only edits have no place to render.

5. **Lint every modified PHP file.** Run:
   ```
   /Users/matthewlawler/Library/Application\ Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php -l <path>
   ```
   on each file you wrote to. If any `php -l` fails, STOP and report — do not push the user toward `git commit`.

6. **Print the summary.** Output:
   - Files modified (absolute paths)
   - Count of strings ported
   - Count of strings flagged as "Unmapped" (couldn't confidently place)
   - `git diff --stat` over `igniteiq/`

7. **Recommend next steps.** Tell the user (don't run any of these yourself):
   - `bash deploy.sh` from repo root → preview at `http://igniteiq.local/`
   - `git add igniteiq/ && git commit -m "Port export <name>"` then `git push origin main`
   - After the GitHub Action deploys to staging, run `/verify-iiq-fidelity`

## Boundaries

- **READS** only: anything under `~/ignite/exports/`, anything under `~/ignite/igniteiq/`, current state of files for context.
- **WRITES** only: files inside `~/ignite/igniteiq/`. Specifically `igniteiq/inc/cli.php`, `igniteiq/template-parts/**/*.php`, `igniteiq/page-*.php`, `igniteiq/front-page.php`, and `igniteiq/assets/js/contact-form.js`. Never `.github/`, never `scripts/`, never repo-root files (`AGENTS.md`, `MIGRATE.md`, `README.md`, `deploy.sh`), never anything outside `igniteiq/`.
- **NEVER** runs `git`, `bash deploy.sh`, `gh`, or `wp` directly. The user does these.
- **NEVER** auto-commits.
- **NEVER** edits the export tree in `exports/`.

## Use existing helpers — don't reinvent

**Available `acf_fc_layout` keys** (do not invent new ones):
- Heroes: `hero_statement`, `hero_editorial`, `hero_minimal`, `hero_split`, `hero_cinematic`
- Sections: `section_prose`, `section_split`, `section_pillars`, `section_stats`, `section_stack`, `section_contrast`, `section_team`
- Trust/CTA: `trust_logos`, `trust_quote`, `cta_banner`
- Specials: `diagram`, `form`

**Diagram keys** (the `diagram_key` field): `stack`, `platform-stack`, `arch-ontology`, `operator-stack`, `cloud-arch`, `framework`, `boundary`.

**Form types** (the `form_type` field): `contact`, `signin`.

**Seed structure pattern.** Read the existing `cli.php` first to mirror its row shape. Each row is a PHP array keyed by `acf_fc_layout` plus that layout's specific fields (e.g. `eyebrow`, `headline`, `subhead`, `body`, `items`, `pillars`, `stats`, `_settings`). Do not invent new field names — match what the renderer at `template-parts/<group>/<name>.php` actually reads.

**Page slugs** (keys of `default_pages()`): `home`, `how-it-works`, `ontology`, `company`, `contact`, `signin`.

## Failure modes

- **String has no obvious target.** Leave a `// FIDELITY EXCEPTION: <reason>` PHP comment near the closest section in the relevant template-part file, and surface it in the summary as "Unmapped — needs manual placement".
- **`php -l` fails on a modified file.** STOP. Report the file path and the parser error. Do not recommend `git commit`. The user fixes the syntax error in their editor and re-runs the skill.
- **`exports/latest/DIFF.md` doesn't exist.** Stop and tell the user to drop a zip in the cloud inbox at `~/Google Drive/My Drive/IgniteIQ/Claude Design Exports/` (the watcher will produce DIFF.md), or run `/diff-iiq-export` manually if they need a fresh diff.
- **`exports/latest` symlink missing.** Stop and tell the user the same — no export to port from.
- **Export string contains a non-content artifact (CSS value, identifier, SVG path).** The extractor is heuristic; some false positives slip through. If a "missing" string is clearly code residue, skip it and note in the summary as "Skipped (extractor false positive): <string>".

## Reminders

- Quote strings byte-accurately. The fidelity contract is: every export string ends up on staging verbatim.
- Edit `cli.php` seed rows AND the matching `template-parts/*.php` markup in lockstep where the markup hardcodes copy. If the renderer reads from ACF, only the seed needs updating.
- Don't widen scope. If the diff has 12 missing strings and you happen to notice 4 unrelated typos elsewhere in `cli.php`, do NOT fix them in this run — flag them in the summary as "Out-of-scope observations" so the user decides.
- Don't deploy. Don't commit. Don't push. The user does.

## Example invocation

```
/port-iiq-diff
```

(no args; reads `exports/latest/DIFF.md`).

Worked example output:

```
Reading exports/latest/DIFF.md… 178 missing strings, 24 extras.

Mapping strings → pages:
  home: 64
  how-it-works: 71
  ontology: 23
  company: 12
  contact: 5
  signin: 3

Editing cli.php (home, how-it-works, ontology, company, contact, signin)…
Editing template-parts/heroes/statement.php (1 hardcoded fallback)…

php -l: 7 files OK.

Files modified:
  /Users/matthewlawler/ignite/igniteiq/inc/cli.php
  /Users/matthewlawler/ignite/igniteiq/template-parts/heroes/statement.php

Strings ported: 175 / 178
Unmapped (3): see // FIDELITY EXCEPTION comments in:
  - igniteiq/template-parts/sections/pillars.php:42
  - igniteiq/template-parts/sections/stats.php:18
  - igniteiq/template-parts/sections/cta-banner.php:24

git diff --stat:
  igniteiq/inc/cli.php                                 | 312 +++++++++++--------
  igniteiq/template-parts/heroes/statement.php         |   4 +-
  2 files changed, 187 insertions(+), 129 deletions(-)

Next steps (run yourself):
  bash deploy.sh                              # preview at http://igniteiq.local/
  git add igniteiq/ && git commit -m "Port export 20260501-pricing-update"
  git push origin main                        # triggers WPE staging deploy
  /verify-iiq-fidelity                        # after staging goes green
```
