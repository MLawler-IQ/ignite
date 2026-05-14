# IgniteIQ WordPress Theme (v2)

Canonical source for the IgniteIQ WordPress theme. **This repo is the source of truth.**

## Repo structure

```
.
├── igniteiq/        # the WordPress theme itself (PHP, CSS, JS, fonts, images)
├── deploy.sh        # rsyncs igniteiq/ → Local Sites theme directory
├── MIGRATE.md       # one-time runbook for activating v2 on a site
└── README.md        # this file
```

`igniteiq/` contains the full theme: `functions.php` bootstrap, page templates, `template-parts/`, `assets/`, and `inc/` (theme setup, ACF field group registration, ACF options page, flexible-content renderer, contact form handler, WP-CLI seed command, and a temporary admin migration tool).

`deploy.sh` is a one-line rsync from this directory's `igniteiq/` into the Local by Flywheel themes folder. Run it from the repo root: `bash deploy.sh`.

`MIGRATE.md` is the activation runbook — only relevant once per environment when first switching from v1 to v2.

## Local dev setup

1. Install [Local by Flywheel](https://localwp.com/) and create a site named `igniteiq` (or use an existing one). The expected path is `~/Local Sites/igniteiq/app/public/`.
2. Install and activate **Advanced Custom Fields Pro** in that site's WP Admin. ACF Pro is required — field groups are registered in PHP at `inc/acf-field-groups.php` (no `acf-json/` sync).
3. From this repo's root, run:
   ```bash
   bash deploy.sh
   ```
   That copies `igniteiq/` into `~/Local Sites/igniteiq/app/public/wp-content/themes/igniteiq-v2/`.
4. WP Admin → Appearance → Themes → activate **IgniteIQ v2**.
5. Seed the six cornerstone pages with default flexible-content. Open Local → right-click the site → **Open site shell**, then:
   ```bash
   wp igniteiq seed --force
   ```
   (Defined in `inc/cli.php`. The `--force` flag overwrites existing `page_sections` with the JSX-derived defaults; omit it to skip pages that already have content.)

   On environments without SSH/WP-CLI, use **Tools → IgniteIQ Migrate** in WP Admin instead. See `MIGRATE.md`.

## Deploy flow (today)

```
Edit in this repo (~/ignite/)
        ↓
bash deploy.sh             (rsync → Local Sites)
        ↓
Local by Flywheel UI       (push to WP Engine staging)
        ↓
WP Engine staging
```

A follow-up session will replace the manual Local-UI push with a **GitHub Action that auto-deploys `main` to WP Engine staging** on every push. Until then, `deploy.sh` + Local's "push to staging" button is the flow.

## Source-of-truth rule

**This repo is canonical.** Any change made directly in `~/Local Sites/.../themes/igniteiq-v2/` or in the WP Admin theme editor is **not authoritative** — it lives only in the runtime mirror and **will be overwritten the next time `deploy.sh` runs**.

If you need to change theme code:
1. Edit it here (in `~/ignite/igniteiq/...`)
2. Commit + push to this repo
3. Run `bash deploy.sh` to mirror to Local
4. Push from Local to WP Engine staging via Local's UI

ACF field group definitions are PHP-registered in `inc/acf-field-groups.php`, not synced via `acf-json/`. Editing field groups in WP Admin is **not persistent** — those changes won't survive a redeploy. Edit the PHP definitions instead.

Page content (the per-page `page_sections` flexible-content rows) is editable in WP Admin and lives in the database — that's expected and not overwritten by deploys. Only theme code is canonical here.

## Collaborators

- **Matt Lawler** — engineering / SEO
- **Scott** — design

## Migration & activation

See [MIGRATE.md](MIGRATE.md) for the one-time v1 → v2 activation runbook (page rename, menu rebuild, seeding).
