# CLAUDE.md — IgniteIQ port loop project context

> Auto-loaded by Claude Code when working in this repo.
> Read `AGENTS.md` at the same path for deeper detail (this file is the cliff-notes; AGENTS.md is the manual).

## What this repo is

WordPress theme for **igniteiq.com**, ported from Claude Design HTML/JS/CSS exports. Theme code lives in `igniteiq/`. Repo root holds deploy tooling, runbooks, and the Cowork plugin source.

> **Two repos, don't confuse them.** "The igniteiq website" = the **marketing site** at igniteiq.com = THIS repo (`MLawler-IQ/ignite`, WP theme). The logged-in **SaaS dashboard** is a *different* repo: `igniteIQ-Platform/igniteiq-frontend` (Vite/React/TS SPA). Default "the igniteiq website" to the marketing site.

- **Local visual dev**: `bash deploy.sh` rsyncs `igniteiq/` → `~/Local Sites/igniteiq/...`. Visit `http://igniteiq.local/`.
- **WP Engine staging**: every push to `main` triggers `.github/workflows/deploy-staging.yml` → rsync to `igniteiqstg.ssh.wpengine.net` → `wp igniteiq seed --force` → `wp page-cache flush` → curl-verify all 6 cornerstone URLs. Live preview at `https://igniteiqstg.wpenginepowered.com/`.
- **Production** is not automated.

## The 6 cornerstone pages
`home` (`/`), `how-it-works`, `ontology`, `company`, `contact`, `signin`.

Each has an ACF flexible-content field `page_sections` populated by `IgniteIQ_CLI::default_pages()` in `igniteiq/inc/cli.php`. Layouts render via `template-parts/{heroes,sections,diagrams,forms}/<name>.php`. Don't bypass this — the seed in `cli.php` and the markup in `template-parts/` must move together.

## Fidelity invariant (load-bearing)

The WP build on staging must exactly match the latest export's content and visuals. A push to `main` is a claim of fidelity.

- **Content**: every headline, body string, button label, footer link, and array of cards/items in `exports/latest/` must appear verbatim on the rendered page.
- **Visuals**: colors (hex), spacing tokens, typography, component order, image src URLs, and CSS class composition must match.

Audit trail: `/diff-iiq-export` output. Verification: `/verify-iiq-fidelity` after each deploy.

If a delta is intentionally not being ported, document it in a `// FIDELITY EXCEPTION:` comment in the relevant `template-parts/*.php` file.

## The skills (5)

| Skill | Purpose | Network needs |
|---|---|---|
| `/fetch-iiq-design` | Fetch a Claude Design handoff URL → unpack into `exports/<dated>/` → repoint `exports/latest`. | `api.anthropic.com` |
| `/diff-iiq-export` | Diff `exports/latest/` against live staging → produce porting checklist with WP theme mapping. | staging URL |
| `/port-iiq-diff` | Edit `cli.php` seed rows + `template-parts/*.php` byte-accurately based on diff output. | none (reads local export + writes local PHP) |
| `/verify-iiq-fidelity` | After deploy, scrape staging and confirm every export string is on rendered HTML. | staging URL |
| `/visual-iiq-diff` | Capture 12 screenshots (export + staging × 6 pages), produce per-page visual gap report. | staging URL + Playwright + Chromium (Mac-local only) |

The 4 fetching skills `curl` by default. From a **Cowork sandbox VM**, only `api.anthropic.com` is reachable; staging URLs return 0 bytes. Fix: skills fall back to the `WebFetch` tool when curl returns empty (or when a `--via=webfetch` flag is set). Visual-iiq-diff cannot run in Cowork (Playwright requires local Chromium).

## The end-to-end loop

1. **Fresh export drops**: handoff URL pasted in Cowork chat (or zip into Google Drive inbox `~/Google Drive/My Drive/IgniteIQ/Claude Design Exports/`)
2. **`/fetch-iiq-design <url>`** → unzips → `exports/latest`
3. **`/diff-iiq-export`** → `DIFF.md` next to the export
4. **`/port-iiq-diff`** → edits PHP, runs `php -l`, stops short of pushing
5. Human review → `git commit && git push origin main`
6. GitHub Action: rsync + seed + cache flush + URL verify (~60s)
7. **`/verify-iiq-fidelity`** → confirms every export string on staging
8. **`/visual-iiq-diff`** (local) → confirms visual fidelity

## Common files

- `igniteiq/inc/cli.php` — seed data for all 6 pages
- `igniteiq/inc/acf-field-groups.php` — ACF layout schemas
- `igniteiq/template-parts/heroes/*.php` — 5 hero variants
- `igniteiq/template-parts/sections/*.php` — 12 section types
- `igniteiq/template-parts/diagrams/*.php` — 5 React-mounted placeholders + 3 PHP-rendered
- `igniteiq/assets/js/iiq-design/*.js` — lifted React components (StackDiagram, PlatformStack, ArchOntologyScene, OperatorStackList, BoundaryDiagram, Reveal)
- `igniteiq/assets/js/iiq-design-bridge.js` — mounts components into `[data-iiq-design]` placeholders
- `scripts/iiq-shoot.js` — Playwright screenshot harness
- `scripts/build-cowork-plugin.sh` — rebuilds the .plugin file
- `cowork-plugin/igniteiq-port/` — the multi-user plugin source

## Local-only PHP binary

```bash
PHP="/Users/matthewlawler/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
"$PHP" -l <file>
```

Used by `/port-iiq-diff` to lint edits before commit. Not needed in Cowork sandbox (PHP not in the VM).

## Don'ts

- Don't add new files to `inc/admin-seed-tool.php` — it's deprecated.
- Don't bypass `cli.php::default_pages()` for seed copy.
- Don't commit anything under `exports/` (gitignored anyway).
- Don't deploy to production from this repo. Staging only.
- Don't paraphrase export copy. Byte-accurate or `// FIDELITY EXCEPTION`.
- Don't push if `php -l` fails on any modified file.
- Don't add a 4th footer column — the grid in `template-parts/footer.php` is hardcoded `repeat(3,1fr)`. Add new links inside an existing column.

## Cowork plugin

Bundled at `cowork-plugin/igniteiq-port/`, built artifact at `cowork-plugin/igniteiq-port.plugin`. Run `bash scripts/build-cowork-plugin.sh` after editing any of the 5 skills (canonical SKILL.md sources are at `~/.claude/skills/<name>/SKILL.md`). Distribution: drag-drop the `.plugin` into Cowork's "Customize" panel.

GitHub auth in Cowork: either OAuth connector OR the GitHub MCP server. Both work; the MCP gives Claude richer per-call tools (`create_or_update_file`, `push_files`, `create_pull_request`, etc.).

## Recent commits worth knowing

- Wave A/B port (rough strings + click-to-port flow + 5 skills)
- WPE page-cache flush added to deploy workflow (otherwise post-deploy HTML is stale)
- Visual fidelity Wave 1 (lifted 5 React diagrams, hero CTA conditionals, signin viewport fix)
- Cowork plugin v0.1.2 (slimmed manifest after server-side validation feedback)
