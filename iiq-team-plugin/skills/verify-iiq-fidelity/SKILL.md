---
name: verify-iiq-fidelity
description: Verify the IgniteIQ WP staging build contains every user-visible string from the latest Claude Design export. Use after a deploy when the user says "verify iiq fidelity", "did staging match the export", or invokes "/verify-iiq-fidelity".
---

# verify-iiq-fidelity

After the GitHub Action deploys the IgniteIQ WP theme to staging, this skill verifies the rendered staging HTML contains every user-visible string from the latest Claude Design export. It enforces Matt's fidelity rule: the WP build must exactly match the export's content.

**This skill is READ-ONLY.** It scrapes URLs and reads export files. It never edits PHP, exports, or staging.

## Inputs / defaults

- Export root: `~/ignite/exports/latest/` (resolve with `readlink`).
- Staging base: `https://igniteiqstg.wpenginepowered.com`.
- Explicit form: `/verify-iiq-fidelity <export-path> <staging-base>`.

If `~/ignite/exports/latest` is missing, stop and emit:
> No exports/latest symlink found. Drop a Claude Design export into exports/<YYYYMMDD>-<name>/ and `ln -snf` to latest first.

## Algorithm

1. **Resolve export root and staging base.** Use `readlink` on `latest` to get the dated dir. If the export nests pages (e.g. `site/<slug>/index.html`), descend into `site/` automatically.

2. **Map export pages → staging URLs** by filename/slug:
   - `index.html` → `/`
   - `how-it-works.html` (or `how-it-works/index.html`) → `/how-it-works/`
   - `ontology.html` → `/ontology/`
   - `company.html` → `/company/`
   - `contact.html` → `/contact/`
   - `signin.html` → `/signin/`

   The 6 cornerstone pages: `home`, `how-it-works`, `ontology`, `company`, `contact`, `signin`.

3. **Extract user-visible strings from each export page.** Strip `<script>...</script>` and `<style>...</style>` blocks, strip remaining tags, decode HTML entities, normalize whitespace (collapse runs to single space, trim). Emit one string per visible text node, deduplicated, length ≥ 4 chars. Suggested one-liner:
   ```bash
   python3 -c "
   import sys, re, html
   from html.parser import HTMLParser
   class V(HTMLParser):
       def __init__(self): super().__init__(); self.skip=0; self.out=[]
       def handle_starttag(self,t,a):
           if t in ('script','style'): self.skip+=1
       def handle_endtag(self,t):
           if t in ('script','style'): self.skip-=1
       def handle_data(self,d):
           if self.skip: return
           s=re.sub(r'\s+',' ',html.unescape(d)).strip()
           if len(s)>=4: self.out.append(s)
   p=V(); p.feed(open(sys.argv[1]).read())
   for s in dict.fromkeys(p.out): print(s)
   " "$FILE"
   ```
   If `python3` isn't available, fall back to `sed -e 's/<script[^>]*>.*<\/script>//g' -e 's/<style[^>]*>.*<\/style>//g' -e 's/<[^>]*>//g' | tr -s '[:space:]' '\n' | awk 'length>=4'`.

4. **Fetch each staging URL with `curl -sL <url>` (local fast path) and run the same extraction.** Strip WP-injected admin bar / wp-emoji / oembed comments before extraction. **Sandbox fallback (Cowork):** if `curl` returns 0 bytes for every URL, the Cowork sandbox is blocking outbound. `WebFetch` is gated to URLs in user messages and won't work programmatically here. Fall back to the **Control Chrome** MCP connector — it drives Chrome on Matt's Mac so the page load happens outside the sandbox. For each URL, call Control Chrome's `open_url` then `get_page_content` to capture the rendered HTML. Treat the returned text as if it were curl's stdout and continue the extraction unchanged. If Control Chrome isn't available, emit: "Cannot reach staging from this environment. Run this skill locally on Matt's Mac, or enable the Control Chrome connector in this Cowork session." and stop.

5. **Diff per-page:** for each export string, check (case-sensitive, whitespace-normalized) whether it appears in the staging string set. List every export string missing from staging as a finding.

6. **Visual-tokens pass (best-effort, indicative only).** For each page, scan rendered HTML's `<style>` blocks, inline `style=` attrs, and class lists. Extract color hex codes (`#[0-9A-Fa-f]{6}`) and class tokens. Flag any color hex or class string that appears in the export but not in staging. Mark this section "indicative, not authoritative" — Tailwind classname-soup makes it brittle.

7. **Don't fail on a single missing page.** Process all 6, then summarize.

## Output format

Write to stdout only. No files.

```
# Fidelity verification: 20260501-pricing-update vs https://igniteiqstg.wpenginepowered.com

✗ 3 strings missing across 2 pages.

## /
✓ Match.

## /how-it-works/
✗ Missing in staging:
- "The architecture lattice that scales with your data."
- "Cross-brand intelligence, regional dashboards, performance benchmarking."

Suggested fix: edit `igniteiq/template-parts/heroes/statement.php` and the `default_pages()['how-it-works'][0]` row in `igniteiq/inc/cli.php`.

## /ontology/
✓ Match.

## /company/
✗ Missing in staging:
- "Founded by operators who scaled $2B+ in managed spend."

Suggested fix: edit `igniteiq/template-parts/sections/prose.php` and the `default_pages()['company'][?]` row in `igniteiq/inc/cli.php`.

## /contact/
✓ Match.

## /signin/
✓ Match.

## Visual tokens (indicative, not authoritative)
- /how-it-works/: color `#A78BFA` present in export, not found in staging HTML.
```

If everything matches, the summary line is `✓ All 6 pages match.` and each section is `✓ Match.`.

## Pitfalls

- **Nested export structure.** Claude Design often emits `site/<slug>/index.html` instead of flat `<slug>.html`. Detect and handle both.
- **Tailwind classname soup.** String-level grep on classes is brittle — that's why visual tokens are flagged "indicative, not authoritative". Don't fail the run on a class miss.
- **WP-injected noise.** Staging HTML contains `wp-emoji`, oembed `<link>`s, admin bar markup, and HTML comments. Strip those before extraction.
- **Whitespace tolerance.** Collapse runs of whitespace to a single space and trim before comparing. Be strict on the words themselves (case-sensitive).
- **Don't fail on one missing page.** Always report all 6 cornerstone pages.

## Invocation

```
/verify-iiq-fidelity
/verify-iiq-fidelity ~/ignite/exports/20260501-pricing-update https://igniteiqstg.wpenginepowered.com
```
