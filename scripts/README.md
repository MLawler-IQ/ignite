# scripts/

Shell tooling that supports the export → port loop. The canonical copy of the watcher
also lives outside `~/Desktop/` (at `~/scripts/igniteiq/watch-exports.sh`) because
macOS TCC blocks `launchd`-spawned processes from reading anything under `~/Desktop/`.
This in-repo copy is the source of truth — keep them in sync if you edit it.

## watch-exports.sh

Triggered by a `launchd` agent whenever the shared Google Drive inbox changes.
Drop a zip into the inbox; ~30 seconds later the export is unzipped into
`exports/<YYYYMMDD>-<derived-name>/`, the `latest` symlink is repointed,
`DIFF.md` is pre-generated against live staging, and an actionable macOS
dialog fires (Cancel / View diff / Open in Claude Code).

### What it does
1. Polls every detected zip's size until 3 reads in a row are identical (guards
   against unzipping a file that's still mid-sync from cloud).
2. Validates the zip: ≤ 500 MB, ≤ 20 000 entries, no path-traversal entries,
   no symlink entries.
3. Unzips into `exports/<YYYYMMDD>-<kebab-cased-name>/`.
4. Repoints `exports/latest` (relative symlink).
5. Pre-generates `DIFF.md` next to the export by running
   `iiq-extract.py` against both the export and live staging, then `comm`-ing
   the two sorted string lists. The dialog (next step) shows live counts
   from this file.
6. Fires an actionable `osascript display dialog` (see "Click-to-port dialog"
   below).
7. Marks the zip as processed in `exports/.processed/`.

### Click-to-port dialog

Once the export is unzipped and `DIFF.md` is on disk, the watcher fires a
modal dialog with three buttons:

- **Cancel** — Dismiss. Nothing happens. The export and DIFF.md still sit
  on disk; you can run `/port-iiq-diff` later by hand. Also fires if the
  10-minute dialog timeout elapses.
- **View diff** — Opens `exports/<dated>/DIFF.md` in your default Markdown
  viewer (whatever `open` resolves to). Read the porting backlog without
  starting a Claude Code session.
- **Open in Claude Code** (default) — Copies `/port-iiq-diff` to the
  clipboard and opens Terminal in the repo root. Run `claude`, then ⌘V Enter
  to fire the port skill. The skill reads `DIFF.md`, edits `cli.php` + the
  matching template-parts, runs `php -l`, and prints a summary. It does NOT
  commit or push — you do that yourself after a `bash deploy.sh` Local check.

The size-stability-failed and unzip-failed paths still use a simple
`display notification` (non-actionable) since there's nothing to click into.

### What it does NOT do
- Does **not** run `/port-iiq-diff` automatically. The inbox is shared with
  Scott; running headless Claude with bypassed permissions on third-party input
  is too risky. The dialog is human-in-loop by design.
- Does not commit, push, or touch any PHP file.

### Logs
Per-fire log at `exports/.watcher.log`. Stdout/stderr from launchd at
`/tmp/igniteiq-export-watcher.{out,err}`.

## One-time setup

```bash
# 1. Copy the canonical script outside ~/Desktop/ (TCC requires this)
mkdir -p ~/scripts/igniteiq
cp ~/ignite/scripts/watch-exports.sh ~/scripts/igniteiq/
chmod +x ~/scripts/igniteiq/watch-exports.sh

# 2. Install the launchd plist
cp ~/ignite/scripts/com.igniteiq.export-watcher.plist \
   ~/Library/LaunchAgents/

# 3. Load it
launchctl bootstrap "gui/$(id -u)" \
  ~/Library/LaunchAgents/com.igniteiq.export-watcher.plist

# 4. Grant Full Disk Access to /bin/bash (REQUIRED — see below)
```

## Granting Full Disk Access

`launchd` agents run in a sandbox that can't read `~/Desktop/` or
`~/Library/CloudStorage/` (Google Drive) without TCC permission. Without this,
the watcher fires but always logs "no zips in inbox".

1. Open **System Settings → Privacy & Security → Full Disk Access**
2. Click **+**
3. Press **⌘⇧G** and type `/bin/bash`, click "Choose"
4. Make sure the toggle next to **bash** is **on**
5. Reload the agent:
   ```bash
   launchctl kickstart -k "gui/$(id -u)/com.igniteiq.export-watcher"
   ```

After this, any zip dropped into the Google Drive inbox auto-processes.

## Troubleshooting

```bash
# Is the agent loaded?
launchctl list | grep igniteiq

# Force a fire (test path, not the watcher)
~/scripts/igniteiq/watch-exports.sh

# Force a fire (via launchd, tests TCC + plist together)
launchctl kickstart -k "gui/$(id -u)/com.igniteiq.export-watcher"

# Tail the watcher log
tail -f ~/ignite/exports/.watcher.log

# Reset processed markers (re-process a previously-handled zip)
rm ~/ignite/exports/.processed/<zip-name>.zip.done

# Unload the agent (stop watching)
launchctl bootout "gui/$(id -u)/com.igniteiq.export-watcher"
```

## The plist

`com.igniteiq.export-watcher.plist` is a copy of what's installed at
`~/Library/LaunchAgents/`. Keep them in sync if you edit either.

## Visual diff harness — `scripts/iiq-shoot.js`

A Playwright-based screenshot harness that captures 12 full-page screenshots
(6 export + 6 staging) for visual fidelity comparison. Used by the
`/visual-iiq-diff` skill, but you can also run it directly:

```bash
cd /tmp && node ~/ignite/scripts/iiq-shoot.js
```

Outputs: `exports/.compare/{export,staging}/{home,how-it-works,ontology,company,contact,signin}.png`

The script:
1. Launches Chromium (Playwright downloads it on first run, ~150 MB)
2. For each of 6 cornerstone pages, navigates to both `file://exports/latest/<file>.html` (export) and `https://igniteiqstg.wpenginepowered.com/<path>/` (staging)
3. Scrolls top→bottom→top to trigger any reveal-on-scroll animations
4. Captures full-page PNG at 1440px wide

Requires Playwright in your project: `npm install playwright` from any dir
that has a node_modules/ context, OR run via `npx -y playwright`.
