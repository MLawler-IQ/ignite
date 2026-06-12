# iiq-team — Claude Code plugin

Team-parity toolkit for operating igniteiq.com via Claude Code. Gives any operator (Matt, Scott, future hires) one-click parity: the `iiq-web-editor` agent, the ops ritual skills, and the `/iiq-setup` bootstrap that installs everything else.

Spec: `.planning/iiq-team-plugin-spec.md`. Conventions: `.planning/parity-plan.md`. Live-site wiring: `OPERATIONS.md`.

## Install

```bash
claude plugin marketplace add MLawler-IQ/ignite
claude plugin install iiq-team@ignite
```

Then inside a session in the repo: `/iiq-setup` (idempotent — safe to re-run). Updates: `claude plugin update iiq-team`.

## What's inside

**Agent:** `iiq-web-editor` — edits theme code and content with voice, fidelity, and deploy discipline. Routes Claude Design handoff URLs into the port loop.

**Ritual skills (authored here):**

| Skill | When |
|---|---|
| `/iiq-setup` | Environment bootstrap — anything feels broken, re-run it |
| `/iiq-check-context` | Start of any session — auth, SSH, clean tree, OPERATIONS.md loaded |
| `/iiq-deploy-prod` | Shipping to production (always use this, never raw `gh workflow run`) |
| `/iiq-env-diff` | "Does staging match prod?" for editable settings |
| `/iiq-verify-mail` | After anything touching email — test contact-form delivery |
| `/iiq-rotate-smtp-pass` | Mail credential rotation (guided) |
| `/iiq-publish-dns` | Any DNS record change (never raw API/dashboard) |
| `/iiq-purge-cf-cache` | Change is live at origin but stale at the edge |
| `/iiq-port-design <url>` | A Claude Design handoff URL dropped — full port loop |

**Port-loop skills (synced from `~/.claude/skills/` by `scripts/build-cowork-plugin.sh --iiq-team` — do not edit here):** `/fetch-iiq-design`, `/diff-iiq-export`, `/port-iiq-diff`, `/verify-iiq-fidelity`, `/visual-iiq-diff`.
