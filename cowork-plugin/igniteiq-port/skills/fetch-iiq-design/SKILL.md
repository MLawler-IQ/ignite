---
name: fetch-iiq-design
description: Fetch a Claude Design handoff URL and unpack it into the IgniteIQ port workspace. Use when the user pastes a Claude Design URL or says "fetch design", "handoff url", "implement design", or "/fetch-iiq-design".
---

# fetch-iiq-design

## What this skill does

Given a Claude Design handoff URL (or just the handoff ID), fetches the public tarball, extracts it into a fresh `~/ignite/exports/<dated>-handoff-<id>/`, repoints the `latest` symlink, and pre-generates `DIFF.md` (export-vs-staging string diff) using the same logic as the legacy `watch-exports.sh` zip-drop watcher. Output is a one-line summary plus the suggested next step (`/port-iiq-diff`).

This is the URL-based replacement for the "zip → cloud inbox → watcher" path. The downstream skills (`/diff-iiq-export`, `/port-iiq-diff`, `/verify-iiq-fidelity`, `/visual-iiq-diff`) consume the resulting `exports/latest/` unchanged.

## Inputs

`/fetch-iiq-design <url-or-id> [short-name]`

Accepted forms for arg 1:
- Full URL with query: `https://api.anthropic.com/v1/design/h/Qc-gT_rMrYCXrKv8IkiznA?open_file=index.html`
- Full URL no query:   `https://api.anthropic.com/v1/design/h/Qc-gT_rMrYCXrKv8IkiznA`
- Bare handoff ID:     `Qc-gT_rMrYCXrKv8IkiznA` (skill prepends the URL)

Optional arg 2: a short-name for the dated directory (kebab-case). If omitted, derived from the first 8 chars of the handoff ID, lowercased, with non-alphanumeric chars replaced by `-`.

If neither arg is provided, stop and ask the user for the URL.

## Algorithm

```bash
INPUT="$1"          # url or id
SHORT_NAME="$2"     # optional override

EXPORTS=~/ignite/exports
mkdir -p "$EXPORTS"

# 1. Extract handoff ID from URL or accept bare ID
ID=$(printf '%s' "$INPUT" | sed -E 's|.*/design/h/([^/?#]+).*|\1|')
# Validate: handoff IDs are URL-safe base64-ish, ~22 chars
if ! printf '%s' "$ID" | grep -qE '^[A-Za-z0-9_-]{8,64}$'; then
  echo "Error: could not extract handoff ID from '$INPUT'." >&2
  echo "Expected: https://api.anthropic.com/v1/design/h/<ID> or bare <ID>." >&2
  exit 1
fi
URL="https://api.anthropic.com/v1/design/h/$ID"

# 2. Compute target dir name
DATE=$(date '+%Y%m%d')
if [ -n "$SHORT_NAME" ]; then
  SHORT=$(printf '%s' "$SHORT_NAME" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+|-+$//g')
else
  SHORT=$(printf '%s' "$ID" | cut -c1-8 | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g')
fi
TARGET="$EXPORTS/${DATE}-handoff-${SHORT}"
[ -e "$TARGET" ] && TARGET="${TARGET}-$(date '+%H%M%S')"

# 3. Fetch into a temp file first so we can validate before extracting
TMP=$(mktemp -t iiq-handoff.XXXXXX)
trap 'rm -f "$TMP"' EXIT

echo "Fetching design $ID..."
HTTP_CODE=$(curl -sL -w '%{http_code}' -o "$TMP" "$URL")
if [ "$HTTP_CODE" != "200" ]; then
  echo "Error: handoff URL returned HTTP $HTTP_CODE." >&2
  echo "  URL: $URL" >&2
  if [ -s "$TMP" ] && [ "$(wc -c < "$TMP")" -lt 4096 ]; then
    echo "  Body: $(cat "$TMP")" >&2
  fi
  exit 1
fi

SIZE=$(wc -c < "$TMP" | tr -d ' ')

# 4. Validate it's actually a gzipped tarball, not an error JSON
if ! file "$TMP" | grep -qE 'gzip compressed|gzip-compressed'; then
  echo "Error: handoff response is not a gzipped tarball." >&2
  echo "  Got: $(file -b "$TMP")" >&2
  if [ "$SIZE" -lt 4096 ]; then
    echo "  Body: $(cat "$TMP")" >&2
  fi
  exit 1
fi

HUMAN_SIZE=$(awk -v b="$SIZE" 'BEGIN{ s="B"; n=b; if(n>1024){n/=1024;s="KB"}; if(n>1024){n/=1024;s="MB"}; printf "%.1f %s", n, s }')
echo "Downloaded $HUMAN_SIZE tarball"

# 5. Extract
mkdir -p "$TARGET"
if ! tar -xzf "$TMP" -C "$TARGET"; then
  echo "Error: tar extract failed." >&2
  rm -rf "$TARGET"
  exit 1
fi
FILE_COUNT=$(find "$TARGET" -type f | wc -l | tr -d ' ')
echo "Extracted $FILE_COUNT files into exports/$(basename "$TARGET")/"

# 6. Repoint latest
( cd "$EXPORTS" && ln -snf "$(basename "$TARGET")" latest )
echo "Pointed exports/latest -> $(basename "$TARGET")"

# 7. Pre-generate DIFF.md (mirrors watch-exports.sh logic)
CONTENT=$(find "$TARGET" -maxdepth 4 -name index.html -print -quit)
if [ -z "$CONTENT" ]; then
  echo "Warning: no index.html found, skipping DIFF.md generation."
  exit 0
fi
CONTENT_DIR=$(dirname "$CONTENT")
STAGING="https://igniteiqstg.wpenginepowered.com"
EX="$HOME/scripts/igniteiq/iiq-extract.py"

python3 "$EX" "$CONTENT_DIR" | LC_ALL=C sort -u > "$TARGET/.export-strings.txt"
{
  for p in / /how-it-works/ /ontology/ /company/ /contact/ /signin/; do
    curl -sL "$STAGING$p" 2>/dev/null; echo
  done
} | python3 "$EX" | LC_ALL=C sort -u > "$TARGET/.staging-strings.txt"

M=$(comm -23 "$TARGET/.export-strings.txt" "$TARGET/.staging-strings.txt" | wc -l | tr -d ' ')
X=$(comm -13 "$TARGET/.export-strings.txt" "$TARGET/.staging-strings.txt" | wc -l | tr -d ' ')

{
  echo "# Diff: Claude Design export -> live staging"
  echo "Export: \`$(basename "$TARGET")\`  ·  Staging: $STAGING"
  echo "Source: handoff $ID"
  echo "Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo
  echo "Missing on staging: $M  ·  Extra on staging: $X"
  echo
  echo "## Missing on staging — porting backlog"
  echo "Each item must end up on staging (in template-part PHP markup AND/OR cli.php seed row)."
  echo
  comm -23 "$TARGET/.export-strings.txt" "$TARGET/.staging-strings.txt" | awk '{ printf "- [ ] %s\n", $0 }'
  echo
  echo "## Extra on staging — review"
  echo "Either outdated copy to remove OR WP-injected (admin bar, accessibility skip-links, RSS) — ignore those."
  echo
  comm -13 "$TARGET/.export-strings.txt" "$TARGET/.staging-strings.txt" | awk '{ printf "- %s\n", $0 }'
} > "$TARGET/DIFF.md"

echo "Generated DIFF.md ($M missing on staging, $X extras)"
echo
echo "Next: /port-iiq-diff"
```

## When curl can't reach staging (Cowork sandbox)

The bash block above uses `curl -sL` — fast on Matt's Mac. In a Cowork session, the sandbox VM blocks all non-Anthropic outbound, so curl silently returns 0 bytes and every export string ends up flagged as "missing on staging". `WebFetch` doesn't help — it's gated to URLs in user messages and can't programmatically fetch the 6 hardcoded paths.

Detect the Cowork case by inspecting `$TARGET/DIFF.md`: if the summary shows `Missing on staging: <large>  ·  Extra on staging: 0`, the curl fetch failed.

When that happens, fall back to the **Control Chrome** MCP connector. Control Chrome drives Chrome on Matt's Mac, so the actual page load happens outside the sandbox:

1. For each of the 6 cornerstone paths (`/`, `/how-it-works/`, `/ontology/`, `/company/`, `/contact/`, `/signin/`):
   - Call Control Chrome's `open_url` with `https://igniteiqstg.wpenginepowered.com<path>`
   - Call Control Chrome's `get_page_content` to capture the rendered HTML
2. Concatenate the returned HTML bodies and pipe through `python3 "$EX"` exactly as the curl loop did, writing `$TARGET/.staging-strings.txt`.
3. Re-run the `comm -23` / `comm -13` set diff against `$TARGET/.export-strings.txt` and rewrite `$TARGET/DIFF.md` with the corrected counts.

If Control Chrome isn't available (connector not enabled in this Cowork session, or Chrome isn't running on Matt's Mac), write a partial `$TARGET/DIFF.md` with this header:

> # Diff: Claude Design export -> live staging (PARTIAL — staging unreachable)
> Re-run /diff-iiq-export locally on Matt's Mac for a real export-vs-staging diff before porting.

Then list every export string as a porting candidate. Do not fail the skill: the unzip + `exports/latest` repoint must always succeed, and the user can still proceed to `/port-iiq-diff` with a "verify locally first" warning.

Stay curl-first. Only enter this fallback when the staging-strings file is empty — never replace the local fast path.

## Output format

Six concise lines (or fewer on early failure). Example:

```
$ /fetch-iiq-design https://api.anthropic.com/v1/design/h/Qc-gT_rMrYCXrKv8IkiznA?open_file=index.html
Fetching design Qc-gT_rMrYCXrKv8IkiznA...
Downloaded 2.0 MB tarball
Extracted 47 files into exports/20260501-handoff-qcgt_rmr/
Pointed exports/latest -> 20260501-handoff-qcgt_rmr
Generated DIFF.md (152 missing on staging, 142 extras)

Next: /port-iiq-diff
```

## Failure modes

- **Bad input** (no URL, mangled URL): clear error pointing at expected forms. No partial dir created.
- **Network failure on curl** (`curl` non-zero exit, or HTTP_CODE != 200): report the HTTP code and a snippet of the body if small. No partial dir.
- **Non-tarball response** (e.g. `not found` text body, error JSON): detected via `file` before any extraction. Reports the actual content type and the body snippet. No partial dir.
- **Tar extract fails** (corrupt archive): reports the failure and removes the empty target dir.
- **Missing `index.html`** in extracted tree: extraction succeeded but the export shape is unfamiliar — print a warning and skip DIFF.md generation. Symlink is still repointed; user can run `/diff-iiq-export` manually after inspecting.
- **`iiq-extract.py` missing** at `~/scripts/igniteiq/iiq-extract.py`: report and skip DIFF.md generation. Don't fail the whole skill — extraction itself is the important part.
- **Staging unreachable** while generating DIFF.md: `curl` returns empty (e.g. Cowork sandbox blocks non-Anthropic outbound). Fall back to the Control Chrome MCP per "When curl can't reach staging (Cowork sandbox)" above. If Control Chrome is also unavailable, write a partial DIFF.md noting "staging unreachable — diff against export only" and continue; the unzip + repoint must always succeed.

## Security note

These handoff URLs come from a third party (Scott or whoever shared the design). The skill:
- Only writes inside `~/ignite/exports/`, which is gitignored.
- Validates the response is a gzipped tarball **before** extracting (via `file`).
- Uses `tar -xzf` (no `-p`, no `--same-owner`); macOS BSD tar refuses absolute paths and `..` traversal entries by default.
- Does **not** execute, source, or eval any code from the export.
- Does not chmod or run anything from the extracted tree.

If this skill ever needs to handle untrusted URLs at scale, add explicit MAX_BYTES + path-traversal guards mirroring `watch-exports.sh`. For the current human-driven workflow (Matt pastes a URL Scott sent him), the gzip + non-executable extraction is sufficient.

## Don'ts

- Don't run a port. Stop after DIFF.md. The user runs `/port-iiq-diff` next.
- Don't write outside `exports/`. No edits to PHP, no commits.
- Don't auto-overwrite an existing dated dir. Append `-HHMMSS` instead.
- Don't follow `?open_file=...` query params or any other URL behavior beyond GET. The handoff endpoint returns the same tarball regardless of query string.

## Example invocation

```
$ /fetch-iiq-design https://api.anthropic.com/v1/design/h/Qc-gT_rMrYCXrKv8IkiznA?open_file=index.html
Fetching design Qc-gT_rMrYCXrKv8IkiznA...
Downloaded 2.0 MB tarball
Extracted 47 files into exports/20260501-handoff-qcgt_rmr/
Pointed exports/latest -> 20260501-handoff-qcgt_rmr
Generated DIFF.md (152 missing on staging, 142 extras)

Next: /port-iiq-diff
```
