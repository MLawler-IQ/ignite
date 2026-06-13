# OPERATIONS.md — IgniteIQ production wiring

> Auto-loaded by Claude Code (via `@OPERATIONS.md` in `CLAUDE.md`).
> CLAUDE.md covers the **port loop**; this file covers everything **live** on the production site (igniteiq.com): deploys, email, DNS, secrets, gotchas. Read this before doing anything that touches production.

---

## Environments

| Env | URL | SSH user@host | Site path | Auto-deploy? |
|---|---|---|---|---|
| Staging | https://igniteiqstg.wpenginepowered.com | `igniteiqstg@igniteiqstg.ssh.wpengine.net` | `sites/igniteiqstg` | Yes — every push to `main` |
| Production | https://igniteiq.com | `igniteiq@igniteiq.ssh.wpengine.net` | `sites/igniteiq` | No — manual gate |

**SSH key (each operator):** `~/.ssh/wpe_deploy_key` (your own, added under your name in the WPE User Portal — do NOT share). Map both hosts to it via `~/.ssh/config`:

```
Host igniteiqstg.ssh.wpengine.net igniteiq.ssh.wpengine.net
  IdentityFile ~/.ssh/wpe_deploy_key
  IdentitiesOnly yes
  ServerAliveInterval 30
```

**Behind the scenes:** WP Engine sites run on Google Cloud Platform; `Received:` headers show `*.bc.googleusercontent.com` reverse-PTRs. Cloudflare is the edge in front of igniteiq.com (proxy-on; cf-cache HIT/MISS visible in response headers).

---

## Deploy flow

### Staging — automatic
Every push to `main` triggers `.github/workflows/deploy-staging.yml`:
1. Rsync `igniteiq/` → `sites/igniteiqstg/wp-content/themes/igniteiq-v2/` (excludes `.git`, `*.bak*`, `node_modules`, `exports/`)
2. `wp igniteiq seed --force` — **overwrites ACF flexible-content on the 6 cornerstones**
3. `wp page-cache flush`
4. Verify all 6 cornerstone URLs return 200
5. **Total ~60s**

### Production — manual, gated
```bash
gh workflow run deploy-production.yml -f confirm=DEPLOY-PRODUCTION --repo MLawler-IQ/ignite
```
Same steps as staging but against the prod site. Gate string is required (typed `DEPLOY-PRODUCTION` — `inputs.confirm` is checked in the `if:` on the job).

**Pre-flight before running prod deploy:**
- Take a WPE prod backup (User Portal → Backups → Create) **unless** the change is purely additive PHP/template
- Confirm no one has hand-edited the 6 cornerstone pages in prod WP admin since the last seed — `wp post list --post_type=page --fields=post_modified` is the read-only check
- Check staging is green: `https://igniteiqstg.wpenginepowered.com/` and the 6 cornerstones load
- Cloudflare edge will serve stale prod HTML for ~10 min after deploy (max-age=600). Hard-refresh or use a `?v=...` query to force fresh fetch.

### What `wp igniteiq seed --force` overwrites vs. leaves alone

| Overwritten on every deploy | Left alone |
|---|---|
| ACF flexible-content (`page_sections`) on home, how-it-works, ontology, company, contact, signin | Site Settings options (`iiq_site_settings`) — including `contact_email` |
| Cornerstone page titles + slugs | Non-cornerstone pages (e.g. `ride-along`) |
| Cornerstone post_modified timestamps | Posts, media, users, comments, menus |
| | wp-content/mu-plugins/* (lives outside theme rsync) |
| | `.htaccess`, `wp-config.php` |

**Rule:** if you want it editable in WP admin without being clobbered, it must live in Site Settings (options page) or outside the 6 cornerstones. If it lives in a seeded cornerstone's `page_sections`, edit the seed in `inc/cli.php` instead.

---

## Email (contact form)

The contact form on `/contact/` sends to **scott@igniteiq.com, matt@igniteiq.com, joshscott@igniteiq.com** via Google Workspace SMTP. All three are on the `To:` header in a single SMTP transaction (intra-tenant fan-out).

### Code path
```
template-parts/forms/contact.php  (form HTML + JS)
  → assets/js/contact-form.js     (POSTs to admin-ajax)
  → inc/contact-form.php          (admin-ajax handler, action=iiq_contact)
  → iiq_contact_recipients()      (reads ACF contact_email, falls back to code default)
  → wp_mail()
  → inc/mail.php                  (PHPMailer hook, gated on IIQ_SMTP_* constants)
```

### SMTP config
The `IIQ_SMTP_HOST / PORT / USER / PASS / SECURE` + `IIQ_MAIL_FROM / FROM_NAME` constants live in **`wp-content/mu-plugins/iiq-smtp.php`** on each environment.

- **mu-plugins is outside the theme rsync target** — deploys never touch it
- Source secrets are repo Actions secrets, written via `configure-mail.yml`
- File is written via `ssh '... cat > file'` (WPE has no SFTP subsystem, scp fails)

### Sender identity
- **From:** `notifications@igniteiq.com` — a Workspace **alias** on the `matt@igniteiq.com` mailbox
- **SMTP auth:** `matt@igniteiq.com` with a Gmail **app password** (not Matt's normal password)
- **Send-as:** Gmail "Send mail as" configured on matt@'s account with `notifications@` as alias, "Treat as alias" enabled. Without Send-as, From would be rewritten to the auth account.

### Editable recipients
- ACF text field `contact_email` on the Site Settings options page (`iiq_site_settings`)
- WP admin path: `/wp-admin/admin.php?page=iiq_site_settings` → "Contact form recipients"
- Format: comma-, semicolon-, or whitespace-separated emails
- Blank value → falls back to code default in `iiq_contact_recipients()`
- Currently seeded with the three addresses on both envs

### configure-mail.yml workflow
```bash
# Re-write SMTP mu-plugin to an environment + test send:
gh workflow run configure-mail.yml \
  -f environment=staging \
  -f send_test=true \
  -f test_to=matt@igniteiq.com

# Same for production:
gh workflow run configure-mail.yml -f environment=production -f send_test=true
```

Use this when rotating the app password, changing the From address, or initial setup of a new environment.

---

## DNS / email authentication

**Provider:** Cloudflare (zone `igniteiq.com`, zone_id `5f6003a1d80fbbca4fb4e687e47c8ba3`).

All three auth records live and pass for outbound mail (verified via external delivery to iCloud, 2026-06-12):

| Record | Value | Notes |
|---|---|---|
| **MX** | `smtp.google.com` | Workspace tenant |
| **SPF** (TXT @) | `v=spf1 include:_spf.google.com include:amazonses.com ~all` | Also authorizes Amazon SES for non-Workspace outbound |
| **DKIM** (TXT `google._domainkey`) | `v=DKIM1; k=rsa; p=...` | Selector `google`, 2048-bit, Workspace-generated, activated in Admin |
| **DMARC** (TXT `_dmarc`) | `v=DMARC1; p=none; rua=mailto:...@dmarc-reports.cloudflare.net` | Monitor-only; reports flow to Cloudflare DMARC Analytics |

**Cloudflare API token:** stored locally only when needed (never committed). Scope: Zone DNS:Edit on `igniteiq.com`. Expires 2027-06-01. Get a fresh one at https://dash.cloudflare.com/profile/api-tokens if needed.

**To advance DMARC from `p=none` to `p=quarantine`/`p=reject`:** first audit DMARC reports (Cloudflare → Email → DMARC Management) for any unauthenticated legitimate senders. Don't tighten until reports show clean alignment for ~2 weeks.

---

## Repo structure — the load-bearing files

```
.github/workflows/
  deploy-staging.yml         # auto on push to main
  deploy-production.yml      # manual, requires confirm=DEPLOY-PRODUCTION
  configure-mail.yml         # manual, env input, writes mu-plugin + test send

igniteiq/                    # theme root (rsync source for deploys)
  inc/
    cli.php                  # SEED DATA — default content for 6 cornerstones
    acf-field-groups.php     # ACF schemas, including iiq_site_settings options page
    acf-options-page.php     # registers Site Settings page + iiq_setting() helper
    contact-form.php         # admin-ajax handler for contact form
    mail.php                 # PHPMailer SMTP hook (gated on IIQ_SMTP_* constants)
    theme-setup.php          # post types, supports, menus
  template-parts/
    nav.php                  # header nav (hardcoded; sign-in link → studio.igniteiq.com)
    footer.php               # footer columns (Platform / Company / Resources)
    forms/contact.php        # contact form UI (aside + form)
    forms/signin.php         # signin form UI
    heroes/*.php             # 5 hero variants
    sections/*.php           # 12 section types
    diagrams/*.php           # diagram placeholders / mounts
  assets/                    # JS, CSS, images, design-system fonts

OPERATIONS.md                # this file
CLAUDE.md                    # port loop + cliff notes
AGENTS.md                    # deeper manual (if present)
```

---

## Repo Actions secrets

Set via `gh secret set --repo MLawler-IQ/ignite`. **Never committed.**

| Secret | Purpose |
|---|---|
| `WPE_SSH_KEY` | Ed25519 private key for the WPE deploy user (used by all 3 workflows) |
| `WPE_SSHG_KEY_PRIVATE` | Older WPE SSH Gateway key (legacy; kept for compatibility) |
| `IIQ_SMTP_HOST` | `smtp.gmail.com` |
| `IIQ_SMTP_USER` | The auth mailbox (currently matt@igniteiq.com) |
| `IIQ_SMTP_PASS` | App password for that mailbox (16-char, generated at myaccount.google.com → Security) |
| `IIQ_MAIL_FROM` | `notifications@igniteiq.com` |
| (optional) `IIQ_SMTP_PORT` | Defaults to 587 if unset |
| (optional) `IIQ_SMTP_SECURE` | Defaults to `tls` if unset |
| (optional) `IIQ_MAIL_FROM_NAME` | Defaults to `IgniteIQ Website` if unset |

To rotate the app password: generate a new one in Google → update `IIQ_SMTP_PASS` secret → re-run `configure-mail.yml` for both envs with `send_test=true`.

---

## Fidelity invariant

The WP build must exactly match `exports/latest/` (the latest Claude Design export):
- Content: every headline, body, button label, list item must appear verbatim on the rendered page
- Visuals: colors, spacing, typography, component order, image src URLs, CSS class composition must match

**Intentional divergences must be marked with `// FIDELITY EXCEPTION:`** in the relevant `template-parts/*.php` file (or as a `<?php // FIDELITY EXCEPTION ... ?>` comment in template markup), with a short reason.

Current FIDELITY EXCEPTIONs in the codebase:
- `template-parts/nav.php` — Sign in link points to `studio.igniteiq.com/#/signin` (external Studio app) instead of `/signin/` (local WP page)
- `template-parts/forms/contact.php` — left column collapsed from Offices (SD HQ + SF) + Direct (email + phone) to a single "Headquartered in San Diego, CA." line
- `page-signin.php` — no global footer (sign-in is full-viewport)

**Verification:** `/verify-iiq-fidelity` after a deploy confirms every export string is present on rendered HTML.

---

## Don't-touch list

These paths require care; never edit without an explicit reason and a backup plan.

| Path / resource | Why |
|---|---|
| `wp-content/mu-plugins/iiq-smtp.php` (both envs) | Contains the live SMTP secrets. Managed exclusively by `configure-mail.yml`. Hand-edits will be silently overwritten on next workflow run. |
| `.github/workflows/*.yml` | Deploy contract. Any change here needs careful review and a tested rollback. |
| Cloudflare DNS records for `igniteiq.com` | Use the API procedure documented below. Don't edit in the Cloudflare dashboard except in emergencies, and announce it. |
| `wp_options` rows backing ACF Site Settings | Edit via WP admin (Site Settings) or `update_field()` via wp-cli. Don't `wp option update options_*` directly — it skips the `_options_*` field-key reference ACF needs. |
| Force-push to `main` | Breaks reproducible deploys; no good reason. |
| `IIQ_SMTP_*` repo secrets | Rotate, don't read — even via gh secret list shows only timestamps. |

---

## Common operations (recipes)

### Add or change a footer link
Edit `igniteiq/template-parts/footer.php` → `$default_columns` array. Each entry: `['label', 'url', 'target' (optional)]`. Add `'target' => '_blank'` for external links.

### Change contact form recipients
Either: WP admin → Site Settings → "Contact form recipients" (immediate, no deploy needed). Or: edit code default in `igniteiq/inc/contact-form.php::iiq_contact_recipients()`.

### Submit a test contact form
```bash
NONCE=$(curl -s -c /tmp/jar 'https://igniteiqstg.wpenginepowered.com/contact/' | grep -oE 'name="nonce" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)".*/\1/')
curl -s -b /tmp/jar -X POST 'https://igniteiqstg.wpenginepowered.com/wp-admin/admin-ajax.php' \
  --data-urlencode "action=iiq_contact" \
  --data-urlencode "nonce=$NONCE" \
  --data-urlencode "website=" \
  --data-urlencode "rendered_at=$(($(date +%s) - 30))" \
  --data-urlencode "first=Test" --data-urlencode "last=User" \
  --data-urlencode "email=test@example.com" --data-urlencode "company=Test" \
  --data-urlencode "topic=Other" --data-urlencode "message=hello"
```
Replace `igniteiqstg.wpenginepowered.com` with `igniteiq.com` for prod. Then check inboxes (or Gmail MCP if available).

### Deploy theme code to production
```bash
gh workflow run deploy-production.yml -f confirm=DEPLOY-PRODUCTION --repo MLawler-IQ/ignite
gh run watch $(gh run list --workflow=deploy-production.yml --repo MLawler-IQ/ignite --limit 1 --json databaseId --jq '.[0].databaseId') --repo MLawler-IQ/ignite --exit-status
```

### Reconfigure SMTP on an environment
```bash
gh workflow run configure-mail.yml -f environment=production -f send_test=true --repo MLawler-IQ/ignite
```

### Purge Cloudflare cache (force fresh edge)
With a `CLOUDFLARE_API_TOKEN` env var (Zone Cache Purge or Zone DNS Edit covers it):
```bash
ZONE_ID=5f6003a1d80fbbca4fb4e687e47c8ba3
curl -s -X POST -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/purge_cache" \
  --data '{"files":["https://igniteiq.com/","https://igniteiq.com/contact/"]}'
```
Or in the dashboard: Caching → Configuration → Purge Cache → Custom Purge.

### Publish/update a DNS record
With `CLOUDFLARE_API_TOKEN` env var:
```bash
# List existing:
curl -s -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?type=TXT&name=_dmarc.igniteiq.com"

# Create new (POST) or update (PUT to /dns_records/<record_id>):
curl -s -X POST -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
  --data '{"type":"TXT","name":"...","content":"...","ttl":3600}'
```

---

## WordPress mail + comments policy

- **`admin_email`** = `notifications@igniteiq.com` on both envs (was WP Engine's placeholder `dev-email@wpengine.local`, which doesn't resolve — it caused NXDOMAIN bounces once real SMTP was wired). All WP system mail (core updates, admin notices) now reaches a real inbox.
- **Comments are OFF site-wide** (this is a marketing/infrastructure site — no blog comments). `default_comment_status=closed`, `default_ping_status=closed`, `comments_notify=0`, `moderation_notify=0`; the default "Hello world!" post (ID 1) has comments closed. Set 2026-06-13 after spam bots on the default post generated bounce emails to `notifications@`.
- These are `wp_options` values — **not** touched by `wp igniteiq seed --force`, so they persist across deploys.
- If bounce emails reappear at `notifications@`: check `wp comment list` (spam on any post) and confirm `default_comment_status` is still `closed`.

## Audit trail

- **Code:** `git log` / `git blame` — every theme/content change in code is attributed.
- **WP admin:** **Simple History** plugin (installed + active on both envs, 2026-06-13) logs post/option/plugin/user events with user + timestamp. View: WP admin → dashboard → Simple History. Default 60-day retention.
- **GitHub branch protection on `main`:** force-pushes and branch deletion blocked (2026-06-13). Direct pushes still allowed — PR review required only for CODEOWNERS paths when PRs are used.

## Cache layers (gotcha)

| Layer | Cleared by | TTL |
|---|---|---|
| WPE page cache (Varnish-ish) | `wp page-cache flush` in deploy workflow | up to 10 min (cleared on deploy) |
| Cloudflare edge cache | **Not auto-purged on deploy** | `max-age=600` (10 min) |
| Browser cache | Hard refresh (`⌘+Shift+R`) | per response Cache-Control |

After a deploy, the WPE origin is fresh immediately but Cloudflare may serve stale HTML for up to ~10 min. Either wait, hard-refresh, or purge CF cache (see recipe above). For visible content changes that need to ship instantly, purge CF.

---

## Operational invariants (read before any production change)

1. **Pull before push.** `git pull --rebase origin main` before `git push origin main`. If rebase has conflicts, stop and read — don't force-push.
2. **PHP-lint before commit.** Use the Local-shipped PHP 8.2 binary (path in CLAUDE.md). `php -l <file>` must succeed.
3. **Staging is the deploy preview.** Push code → check staging → only then deploy prod.
4. **Cornerstone admin edits are ephemeral.** If you edit a cornerstone's `page_sections` in WP admin, the next deploy's `wp igniteiq seed --force` wipes it. Edit the seed in `inc/cli.php` instead.
5. **Don't touch secrets in chat.** If a credential is shared in chat (e.g., API token, app password), treat the chat history as compromised — rotate the credential at end of session.
