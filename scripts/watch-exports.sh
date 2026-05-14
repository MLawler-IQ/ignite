#!/usr/bin/env bash
# Watcher fired by launchd whenever the Google Drive inbox changes.
# Finds any *.zip not yet processed, waits for sync to complete, validates
# the zip, unzips into exports/<YYYYMMDD>-<derived-name>/, repoints `latest`,
# pre-generates DIFF.md (export-vs-staging string diff), then fires an
# actionable osascript dialog. Matt clicks "Open in Claude Code" → Terminal
# opens in the repo with `/port-iiq-diff` already on the clipboard.
#
# Why no auto-port: the inbox is shared (Scott drops here too). Running
# headless Claude with bypassed permissions on third-party input is too
# risky. The watcher does the file plumbing + diff; the port stays
# human-in-loop under visual review.
#
# Logs to exports/.watcher.log. Idempotent — uses .processed/ markers.

set -uo pipefail

INBOX="$HOME/Google Drive/My Drive/IgniteIQ/Claude Design Exports"
REPO="$HOME/ignite"
EXPORTS="$REPO/exports"
LOG="$EXPORTS/.watcher.log"
PROCESSED_DIR="$EXPORTS/.processed"

# Safety limits.
MAX_ZIP_BYTES=$((500 * 1024 * 1024))   # 500 MB hard cap on the zip itself
MAX_FILES=20000                         # too many entries → likely zip bomb / not a Claude Design export

mkdir -p "$EXPORTS" "$PROCESSED_DIR"
exec >> "$LOG" 2>&1

ts() { date '+%Y-%m-%d %H:%M:%S'; }
log() { echo "[$(ts)] $*"; }

# Plain non-actionable notification, used for failure paths only.
notify() {
  local title="$1" subtitle="$2" body="$3"
  /usr/bin/osascript -e "display notification \"${body//\"/\\\"}\" with title \"${title//\"/\\\"}\" subtitle \"${subtitle//\"/\\\"}\""
}

log "watcher fired"

shopt -s nullglob
zips=("$INBOX"/*.zip "$INBOX"/*.ZIP)
if [ "${#zips[@]}" -eq 0 ]; then
  log "no zips in inbox; exit"
  exit 0
fi

for zip in "${zips[@]}"; do
  marker="$PROCESSED_DIR/$(basename "$zip").done"
  if [ -f "$marker" ]; then
    continue
  fi

  log "candidate: $zip"

  # Wait for size to stabilize (3 consecutive identical reads, 2s apart).
  # Guards against unzipping a half-synced cloud file.
  prev=-1
  same=0
  for i in $(seq 1 60); do
    cur=$(stat -f%z "$zip" 2>/dev/null || echo -1)
    if [ "$cur" -eq "$prev" ] && [ "$cur" -gt 0 ]; then
      same=$((same + 1))
    else
      same=0
    fi
    if [ "$same" -ge 2 ]; then
      log "size stable at $cur bytes after ${i}s"
      break
    fi
    prev=$cur
    sleep 2
  done

  if [ "$same" -lt 2 ]; then
    log "size never stabilized; skipping (will retry next fire)"
    continue
  fi

  # Derive directory name: <YYYYMMDD>-<kebab-cased-zip-name>.
  base=$(basename "$zip")
  base="${base%.[zZ][iI][pP]}"
  short=$(printf '%s' "$base" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+|-+$//g')
  date_prefix=$(date '+%Y%m%d')
  target="$EXPORTS/${date_prefix}-${short}"

  if [ -e "$target" ]; then
    target="${target}-$(date '+%H%M%S')"
  fi

  # Safety pass: reject suspicious zips before extracting.
  zip_size=$(stat -f%z "$zip")
  if [ "$zip_size" -gt "$MAX_ZIP_BYTES" ]; then
    log "REJECT: zip is $zip_size bytes, exceeds MAX_ZIP_BYTES=$MAX_ZIP_BYTES"
    notify "IgniteIQ export REJECTED" "$(basename "$zip")" "Zip too large — see .watcher.log"
    touch "$marker"
    continue
  fi

  if ! listing=$(unzip -l "$zip" 2>/dev/null); then
    log "REJECT: unzip -l failed (corrupt zip?)"
    notify "IgniteIQ export REJECTED" "$(basename "$zip")" "Corrupt zip — see .watcher.log"
    touch "$marker"
    continue
  fi

  entry_count=$(echo "$listing" | tail -1 | awk '{print $2}')
  if [ -n "$entry_count" ] && [ "$entry_count" -gt "$MAX_FILES" ]; then
    log "REJECT: $entry_count entries exceeds MAX_FILES=$MAX_FILES"
    notify "IgniteIQ export REJECTED" "$(basename "$zip")" "Too many files — see .watcher.log"
    touch "$marker"
    continue
  fi

  # Reject path-traversal entries (../, absolute paths) and symlinks.
  if echo "$listing" | awk 'NR>3 {print $4}' | grep -qE '(^/|\.\./|^\.\./)'; then
    log "REJECT: zip contains path-traversal entries"
    notify "IgniteIQ export REJECTED" "$(basename "$zip")" "Suspicious paths — see .watcher.log"
    touch "$marker"
    continue
  fi
  if unzip -Z1l "$zip" 2>/dev/null | head -200 | xargs -I{} unzip -Zv "$zip" "{}" 2>/dev/null | grep -q "^lrw"; then
    log "REJECT: zip contains symlink entries"
    notify "IgniteIQ export REJECTED" "$(basename "$zip")" "Contains symlinks — see .watcher.log"
    touch "$marker"
    continue
  fi

  log "unzipping → $target"
  mkdir -p "$target"
  if ! unzip -q "$zip" -d "$target"; then
    log "unzip FAILED for $zip"
    rmdir "$target" 2>/dev/null || true
    notify "IgniteIQ export FAILED" "$(basename "$zip")" "Unzip failed — see .watcher.log"
    continue
  fi

  # Repoint latest symlink (relative target keeps it portable).
  cd "$EXPORTS" && ln -snf "$(basename "$target")" latest
  log "latest → $(basename "$target")"

  # Pre-generate DIFF.md so the dialog has accurate counts and the user has
  # something to read in "View diff". Also primes /port-iiq-diff's input.
  # Resolve content dir (may be a `site/` subdir, `igniteiq-website/project/`, or flat)
  CONTENT=$(find "$target" -maxdepth 4 -name index.html -print -quit)
  M=0
  X=0
  if [ -n "$CONTENT" ]; then
    CONTENT_DIR=$(dirname "$CONTENT")
    STAGING="https://igniteiqstg.wpenginepowered.com"
    EX="$HOME/scripts/igniteiq/iiq-extract.py"

    python3 "$EX" "$CONTENT_DIR" | LC_ALL=C sort -u > "$target/.export-strings.txt"
    {
      for p in / /how-it-works/ /ontology/ /company/ /contact/ /signin/; do
        curl -sL "$STAGING$p" 2>/dev/null; echo
      done
    } | python3 "$EX" | LC_ALL=C sort -u > "$target/.staging-strings.txt"

    M=$(comm -23 "$target/.export-strings.txt" "$target/.staging-strings.txt" | wc -l | tr -d ' ')
    X=$(comm -13 "$target/.export-strings.txt" "$target/.staging-strings.txt" | wc -l | tr -d ' ')

    # Write a compact DIFF.md for human review
    {
      echo "# Diff: Claude Design export → live staging"
      echo "Export: \`$(basename "$target")\`  ·  Staging: $STAGING"
      echo "Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
      echo
      echo "Missing on staging: $M  ·  Extra on staging: $X"
      echo
      echo "## Missing on staging — porting backlog"
      echo "Each item must end up on staging (in template-part PHP markup AND/OR cli.php seed row)."
      echo
      comm -23 "$target/.export-strings.txt" "$target/.staging-strings.txt" | awk '{ printf "- [ ] %s\n", $0 }'
      echo
      echo "## Extra on staging — review"
      echo "Either outdated copy to remove OR WP-injected (admin bar, accessibility skip-links, RSS) — ignore those."
      echo
      comm -13 "$target/.export-strings.txt" "$target/.staging-strings.txt" | awk '{ printf "- %s\n", $0 }'
    } > "$target/DIFF.md"

    log "DIFF.md written: $M missing, $X extra"
  else
    log "no index.html found in $target — skipping diff generation"
  fi

  # Actionable dialog with three buttons. 10-minute timeout.
  result=$(/usr/bin/osascript <<APPLESCRIPT 2>/dev/null
try
  with timeout of 600 seconds
    set theResult to display dialog "$(printf 'New IgniteIQ export ready: %s.\\n\\n%d strings missing on staging.\\n%d extras on staging (review).' "$(basename "$target")" "${M:-0}" "${X:-0}")" buttons {"Cancel", "View diff", "Open in Claude Code"} default button "Open in Claude Code" with title "IgniteIQ export ready" with icon note
    return button returned of theResult
  end timeout
on error
  return "Cancel"
end try
APPLESCRIPT
)

  case "$result" in
    "View diff")
      /usr/bin/open "$target/DIFF.md"
      log "user: View diff"
      ;;
    "Open in Claude Code")
      # Prime clipboard with the slash command
      printf '%s' "/port-iiq-diff" | /usr/bin/pbcopy
      # Open the repo dir in Terminal so the user can fire `claude` there.
      # macOS has no documented `claude --prompt` flag yet; this is the cleanest
      # cross-version invocation. User pastes ⌘V Enter once Claude is running.
      /usr/bin/open -a Terminal "$HOME/ignite"
      log "user: Open in Claude Code"
      ;;
    *)
      log "user: Cancel (or dialog timed out)"
      ;;
  esac

  # Mark zip as processed so we don't redo it on the next watcher fire.
  touch "$marker"
  log "marked processed: $marker"
done

shopt -u nullglob

log "watcher done"
