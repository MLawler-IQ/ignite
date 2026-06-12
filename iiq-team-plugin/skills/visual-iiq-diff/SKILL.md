---
name: visual-iiq-diff
description: Capture screenshots of all 6 IgniteIQ pages on both the export and live staging, then produce a per-page visual gap report. Use when the user says "visual diff iiq", "screenshot diff", or invokes "/visual-iiq-diff".
---

# visual-iiq-diff

Capture full-page screenshots of all 6 cornerstone IgniteIQ pages on both the local Claude Design export and the live WP staging site, then produce a markdown gap report calling out every visual mismatch by page and severity. Use this after `/port-iiq-diff` lands to confirm visual fidelity that string-level skills (`/diff-iiq-export`, `/verify-iiq-fidelity`) can't catch — diagram drift, spacing, typography, missing reveals, broken CTAs.

**This skill is READ-ONLY.** Captures screenshots + reads them. Never edits PHP, exports, staging, or the harness script.

**This skill must run locally on Matt's Mac.** The Cowork VM doesn't have Chromium; Playwright cannot launch a browser inside the sandbox.

## Prerequisites

- The harness `~/ignite/scripts/iiq-shoot.js` uses Playwright. Playwright must be installed in the cwd's `node_modules/` (or globally). If the require fails: `npm install playwright` from any dir with a `node_modules/` context, or `npx -y playwright install chromium` first. On first run Playwright downloads Chromium (~150 MB).
- `~/ignite/exports/latest` must exist (symlink to a dated export with the 6 cornerstone HTML files at root).
- Network access required (staging URLs).

## Algorithm

1. **Sanity-check inputs.** Confirm `~/ignite/exports/latest` resolves and the 6 expected `.html` files exist directly under the resolved dir (or under its content root — inherit detection from `/diff-iiq-export` if the export nests under `site/` or `igniteiq-website/project/`). If `latest` is missing, stop with the same hint as `/diff-iiq-export`.

2. **Run the screenshot harness.**
   ```bash
   cd /tmp && node ~/ignite/scripts/iiq-shoot.js
   ```
   It writes 12 PNGs into `~/ignite/exports/.compare/{export,staging}/{home,how-it-works,ontology,company,contact,signin}.png`. Stderr emits one line per shot — surface any `goto failed` or `screenshot failed` lines as page-level warnings (don't abort the whole run).

3. **For each of the 6 pages, compare visually.** Read both PNGs with the Read tool (it presents images visually to the model). For each pair, look at: hero composition (eyebrow + headline + body + CTA), section ordering, diagram fidelity (the export's React/SVG diagrams vs the WP theme's PHP/SVG approximations are the highest-risk drift area), reveal-on-scroll completeness, spacing rhythm, typography scale, footer presence/contents.

4. **Build the gap report.** One per-page table with severity, gap description, root cause hypothesis. Then root-cause groupings (issues that share a fix) and a wave-ordered correction plan.

5. **Save and print.** Write the report to `exports/.compare/GAPS-<YYYYMMDD-HHMMSS>.md` and also print to stdout. Use UTC for the timestamp suffix.

## The screenshot harness

Canonical script: `~/ignite/scripts/iiq-shoot.js`. It launches headless Chromium at 1440x900 viewport, loops over the 6 pages, and for each visits both the `file://` export and the `https://igniteiqstg.wpenginepowered.com/<path>/` staging URL. Per-page steps: `goto` with `waitUntil: 'networkidle'`, scroll top→bottom (400px steps every 80ms) to trigger reveal-on-scroll animations, scroll back to top, wait 800ms for in-flight animations, then `fullPage: true` screenshot. Don't edit this script from inside the skill — it's the canonical version.

## Per-page comparison

For every cornerstone page, read both pngs and compare. Use absolute paths:

```
~/ignite/exports/.compare/export/<slug>.png
~/ignite/exports/.compare/staging/<slug>.png
```

Slugs: `home`, `how-it-works`, `ontology`, `company`, `contact`, `signin`.

Severity scale (mirror `GAPS.md`):
- 🔴 **critical** — diagram broken, hero missing/wrong, content missing, layout collapsed
- 🟠 **high** — element rendering but wrong (oversized CTA, misplaced block, wrong-color fill)
- 🟡 **medium** — typography or spacing drift, minor alignment, font-weight differences
- 🟢 **low** — matches; flag only if there's something useful to note

Number gaps per page with a page-prefix code: home → `H1, H2, …`, how-it-works → `HW1, HW2, …`, ontology → `O1, …`, company → `C1, …`, contact → `CT1, …`, signin → `SI1, …`. This makes the report cross-referenceable in fix waves.

## Output format

Mimic `~/ignite/exports/.compare/GAPS.md` exactly:

```
# Visual fidelity gaps: export vs staging

Method: 12 full-page screenshots (1440px wide) captured <YYYY-MM-DD>:
- Export: file:// rendering of `~/ignite/exports/latest/{*.html}`
- Staging: live `https://igniteiqstg.wpenginepowered.com/{*}`
- Both with reveal-on-scroll triggered (scrolled bottom-to-top before capture)

Files: `exports/.compare/{export,staging}/{home,how-it-works,ontology,company,contact,signin}.png`

---

## Per-page gaps

### Home — `/`

| # | Severity | Gap | Root cause |
|---|---|---|---|
| H1 | 🔴 critical | <description> | <hypothesis> |
...

### How-it-works — `/how-it-works/`
...
### Ontology — `/ontology/`
...
### Company — `/company/`
...
### Contact — `/contact/`
...
### Sign-in — `/signin/`
...

---

## Root cause groupings

1. **<theme>** (N occurrences: H1, HW1, …): <description>. **Fix: <approach>.**
2. ...

---

## Plan to correct all — N waves, dependency-ordered

### Wave D-1 — <name> (<estimate>)
- <step>
- ...

**Closes:** <issue codes>

...

## Estimated total time
<estimate>

## Order of operations
<dependency notes>

## Verification at each gate
- php -l on every modified PHP file
- bash deploy.sh + manual walkthrough
- Push to main → GitHub Action green
- Re-run /visual-iiq-diff for regression
- Run /verify-iiq-fidelity for string-level regression
```

If a page is fully clean: a single row with severity 🟢 and `— matches —` is fine; never omit a page.

## Pitfalls

- **Reveal-on-scroll not firing.** The harness already scrolls top→bottom→top with a 600ms settle, then waits another 800ms. If a long page still misses reveals, increase the per-tick delay in the harness — but don't edit the harness from this skill; flag it in the report instead.
- **SPA exports need `networkidle`.** The harness already passes `waitUntil: 'networkidle'`. If a page renders blank in the export shot, suspect a JS bundle 404 (export missing `js/*.js`) — read the harness stderr.
- **Staging CDN cache.** WP Engine's CDN can serve a stale page right after a deploy. If staging shots look pre-deploy, wait 1–2 minutes and re-run.
- **Diagram drift is the canonical pattern.** Every PHP/SVG approximation of an export's React+SVG diagram has shipped with visible drift. Always check `template-parts/diagrams/*.php` first when a 🔴 lands on a diagram element.
- **Don't fail on one bad shot.** If `goto failed` for a single page, surface the warning and continue with the other 5.

## Invocation

```
/visual-iiq-diff
```

No arguments. Output: stdout report + saved file at `~/ignite/exports/.compare/GAPS-<YYYYMMDD-HHMMSS>.md`.

## Reportback

- Path to the saved GAPS-<ts>.md
- Total gap counts by severity (🔴 N, 🟠 N, 🟡 N, 🟢 N)
- The wave plan in 1–2 sentences (e.g. "D-1 lifts 4 React diagrams; D-2 fixes 2 CTA conditions; D-3 spacing pass")
- Any pages where the harness failed to capture
