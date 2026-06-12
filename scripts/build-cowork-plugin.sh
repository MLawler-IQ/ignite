#!/usr/bin/env bash
# build-cowork-plugin.sh
#
# Build the IgniteIQ Port Cowork plugin artifact:
#   cowork-plugin/igniteiq-port.plugin
#
# Steps:
#   1. Refresh user-scoped skills from ~/.claude/skills/ into the plugin tree
#   2. Validate plugin.json + .mcp.json parse as JSON
#   3. Lint each SKILL.md has YAML frontmatter (---, name:, description:)
#   4. Zip the plugin dir contents into cowork-plugin/igniteiq-port.plugin
#   5. Print size + unzip listing
#
# Usage:
#   bash scripts/build-cowork-plugin.sh             # one-shot build (Cowork zip)
#   bash scripts/build-cowork-plugin.sh --watch     # rebuild on SKILL.md changes (requires fswatch)
#   bash scripts/build-cowork-plugin.sh --iiq-team  # iiq-team plugin: sync port-loop skills,
#                                                   # validate plugin.json + marketplace.json,
#                                                   # lint SKILL.md + agent frontmatter. NO zip
#                                                   # (local installs consume the directory via
#                                                   # the repo marketplace). Exits non-zero on
#                                                   # any validation failure (CI-friendly).

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$REPO_ROOT/cowork-plugin/igniteiq-port"
ARTIFACT="$REPO_ROOT/cowork-plugin/igniteiq-port.plugin"
SKILLS_SRC="$HOME/.claude/skills"
SKILLS_DST="$PLUGIN_DIR/skills"
SKILL_NAMES=(
  fetch-iiq-design
  diff-iiq-export
  port-iiq-diff
  verify-iiq-fidelity
  visual-iiq-diff
)

color() { printf "\033[%sm%s\033[0m\n" "$1" "$2"; }
info() { color "0;36" "[info] $*"; }
warn() { color "1;33" "[warn] $*"; }
err()  { color "1;31" "[err]  $*"; }
ok()   { color "0;32" "[ok]   $*"; }

build_once() {
  info "repo root:  $REPO_ROOT"
  info "plugin dir: $PLUGIN_DIR"

  if [[ ! -d "$PLUGIN_DIR/.claude-plugin" ]]; then
    err ".claude-plugin/ missing under $PLUGIN_DIR — scaffold not created. Aborting."
    exit 1
  fi

  # 1. Refresh skills
  mkdir -p "$SKILLS_DST"
  local copied=0 missing=()
  for s in "${SKILL_NAMES[@]}"; do
    local src="$SKILLS_SRC/$s"
    local dst="$SKILLS_DST/$s"

    if [[ ! -d "$src" ]] || [[ ! -f "$src/SKILL.md" ]]; then
      warn "skill not found: $src (will skip — likely still being built)"
      missing+=("$s")
      continue
    fi

    rm -rf "$dst"
    cp -R "$src" "$dst"
    copied=$((copied + 1))
    ok "refreshed skill: $s"
  done

  if (( ${#missing[@]} > 0 )); then
    warn "missing skills (plugin will ship without them): ${missing[*]}"
  fi
  info "copied $copied / ${#SKILL_NAMES[@]} skills"

  # 2. Validate JSON
  validate_json() {
    local file="$1"
    if [[ ! -f "$file" ]]; then
      err "$file not found"; exit 1
    fi
    if command -v jq >/dev/null 2>&1; then
      jq empty "$file" >/dev/null
    else
      python3 -m json.tool "$file" >/dev/null
    fi
    ok "valid JSON: $(basename "$file")"
  }
  validate_json "$PLUGIN_DIR/.claude-plugin/plugin.json"
  # .mcp.json is optional — Cowork plugins typically rely on the desktop app's
  # built-in connector UI for MCP servers rather than bundling them.
  if [[ -f "$PLUGIN_DIR/.mcp.json" ]]; then
    validate_json "$PLUGIN_DIR/.mcp.json"
  fi

  # 3. Lint each SKILL.md frontmatter
  local lint_errs=0
  while IFS= read -r -d '' skill_md; do
    # Must start with --- on line 1, contain name: and description: in the frontmatter block
    local first_line
    first_line=$(head -n 1 "$skill_md")
    if [[ "$first_line" != "---" ]]; then
      err "missing YAML frontmatter opener (---) in $skill_md"
      lint_errs=$((lint_errs + 1))
      continue
    fi
    # Pull frontmatter block: lines 2..(next ---)
    local fm
    fm=$(awk 'NR==1 && $0=="---"{flag=1;next} flag && $0=="---"{exit} flag{print}' "$skill_md")
    if ! grep -qE '^name:[[:space:]]+' <<<"$fm"; then
      err "missing 'name:' in frontmatter: $skill_md"
      lint_errs=$((lint_errs + 1))
    fi
    if ! grep -qE '^description:[[:space:]]+' <<<"$fm"; then
      err "missing 'description:' in frontmatter: $skill_md"
      lint_errs=$((lint_errs + 1))
    fi
    if (( lint_errs == 0 )); then
      ok "frontmatter valid: $(basename "$(dirname "$skill_md")")/SKILL.md"
    fi
  done < <(find "$SKILLS_DST" -mindepth 2 -maxdepth 2 -name SKILL.md -print0)

  if (( lint_errs > 0 )); then
    err "$lint_errs SKILL.md lint failure(s) — aborting"
    exit 1
  fi

  # 4. Zip
  rm -f "$ARTIFACT"
  ( cd "$PLUGIN_DIR" && zip -qr "$ARTIFACT" . -x "*.DS_Store" )
  ok "wrote $ARTIFACT"

  # 5. Print size + listing
  local size
  size=$(du -h "$ARTIFACT" | awk '{print $1}')
  info "size: $size"
  info "contents:"
  unzip -l "$ARTIFACT"
}

# --- iiq-team target -------------------------------------------------------
# Sync the 5 canonical port-loop skills into iiq-team-plugin/skills/, validate
# the plugin + marketplace manifests, lint every SKILL.md and agent .md for
# required frontmatter. No zip — the iiq-team plugin installs from the repo
# directory via the root .claude-plugin/marketplace.json.

lint_frontmatter_file() {
  # args: <file>; returns number of problems via global FM_ERRS
  local md="$1" first_line fm errs=0
  if [[ ! -f "$md" ]]; then
    err "file not found: $md"
    FM_ERRS=$((FM_ERRS + 1))
    return
  fi
  first_line=$(head -n 1 "$md")
  if [[ "$first_line" != "---" ]]; then
    err "missing YAML frontmatter opener (---) in $md"
    FM_ERRS=$((FM_ERRS + 1))
    return
  fi
  fm=$(awk 'NR==1 && $0=="---"{flag=1;next} flag && $0=="---"{exit} flag{print}' "$md")
  if ! grep -qE '^name:[[:space:]]+' <<<"$fm"; then
    err "missing 'name:' in frontmatter: $md"
    errs=$((errs + 1))
  fi
  if ! grep -qE '^description:[[:space:]]+' <<<"$fm"; then
    err "missing 'description:' in frontmatter: $md"
    errs=$((errs + 1))
  fi
  if (( errs == 0 )); then
    ok "frontmatter valid: ${md#$REPO_ROOT/}"
  else
    FM_ERRS=$((FM_ERRS + errs))
  fi
}

build_iiq_team() {
  local IIQ_DIR="$REPO_ROOT/iiq-team-plugin"
  local IIQ_SKILLS="$IIQ_DIR/skills"
  info "repo root:       $REPO_ROOT"
  info "iiq-team plugin: $IIQ_DIR"

  if [[ ! -d "$IIQ_DIR/.claude-plugin" ]]; then
    err ".claude-plugin/ missing under $IIQ_DIR — scaffold not created. Aborting."
    exit 1
  fi

  # 1. Sync the 5 port-loop skills from canonical ~/.claude/skills/
  mkdir -p "$IIQ_SKILLS"
  local synced=0 missing=()
  for s in "${SKILL_NAMES[@]}"; do
    local src="$SKILLS_SRC/$s"
    local dst="$IIQ_SKILLS/$s"
    if [[ ! -d "$src" ]] || [[ ! -f "$src/SKILL.md" ]]; then
      err "canonical skill not found: $src"
      missing+=("$s")
      continue
    fi
    rm -rf "$dst"
    cp -R "$src" "$dst"
    synced=$((synced + 1))
    ok "synced skill: $s"
  done
  if (( ${#missing[@]} > 0 )); then
    err "missing canonical skills: ${missing[*]} — aborting (iiq-team must bundle all 5)"
    exit 1
  fi
  info "synced $synced / ${#SKILL_NAMES[@]} port-loop skills"

  # 2. Validate JSON manifests
  validate_json() {
    local file="$1"
    if [[ ! -f "$file" ]]; then
      err "$file not found"; exit 1
    fi
    if command -v jq >/dev/null 2>&1; then
      jq empty "$file" >/dev/null
    else
      python3 -m json.tool "$file" >/dev/null
    fi
    ok "valid JSON: ${file#$REPO_ROOT/}"
  }
  validate_json "$IIQ_DIR/.claude-plugin/plugin.json"
  validate_json "$REPO_ROOT/.claude-plugin/marketplace.json"

  # 3. Lint frontmatter: every SKILL.md + every agent .md
  FM_ERRS=0
  local skill_count=0
  while IFS= read -r -d '' skill_md; do
    lint_frontmatter_file "$skill_md"
    skill_count=$((skill_count + 1))
  done < <(find "$IIQ_SKILLS" -mindepth 2 -maxdepth 2 -name SKILL.md -print0 | sort -z)
  if (( skill_count == 0 )); then
    err "no SKILL.md files found under $IIQ_SKILLS"
    exit 1
  fi

  local agent_count=0
  while IFS= read -r -d '' agent_md; do
    lint_frontmatter_file "$agent_md"
    agent_count=$((agent_count + 1))
  done < <(find "$IIQ_DIR/agents" -maxdepth 1 -name '*.md' -print0 2>/dev/null | sort -z)
  if (( agent_count == 0 )); then
    err "no agent .md files found under $IIQ_DIR/agents"
    exit 1
  fi

  if (( FM_ERRS > 0 )); then
    err "$FM_ERRS frontmatter lint failure(s) — aborting"
    exit 1
  fi

  # 4. No zip. Summary only.
  ok "iiq-team plugin valid: $skill_count skills, $agent_count agent(s). No artifact — installs via repo marketplace."
}

watch_loop() {
  if ! command -v fswatch >/dev/null 2>&1; then
    err "fswatch not found. Install with: brew install fswatch"
    exit 1
  fi
  info "watching $SKILLS_SRC for SKILL.md changes (Ctrl-C to stop)"
  build_once
  fswatch -o \
    "$SKILLS_SRC/fetch-iiq-design" \
    "$SKILLS_SRC/diff-iiq-export" \
    "$SKILLS_SRC/port-iiq-diff" \
    "$SKILLS_SRC/verify-iiq-fidelity" \
    "$SKILLS_SRC/visual-iiq-diff" 2>/dev/null \
    | while read -r _; do
        info "change detected — rebuilding"
        build_once || warn "build failed; will retry on next change"
      done
}

case "${1:-}" in
  --watch) watch_loop ;;
  --iiq-team) build_iiq_team ;;
  ""|--once) build_once ;;
  *)
    err "unknown arg: $1"
    echo "usage: $0 [--once|--watch|--iiq-team]" >&2
    exit 2
    ;;
esac
