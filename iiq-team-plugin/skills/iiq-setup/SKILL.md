---
name: iiq-setup
description: One-time environment bootstrap for igniteiq.com operators — installs deps, wires SSH, validates gh auth, writes starter Claude Code permissions, runs the context preflight. Idempotent; re-run any time something feels broken, or when the user says "set me up", "bootstrap my environment", or invokes "/iiq-setup".
---

# iiq-setup

*Ritual: environment bootstrap, 8 steps in order. Partial completion leaves a confusing half-configured machine — that's why this is a skill, not a checklist in a doc.*

**Idempotent.** Every step checks before it acts. Safe to re-run anytime to revalidate. The only prerequisite this skill cannot automate: `brew install node` (needs an admin password) — if `node` is missing, stop and tell the operator to run ONBOARDING.md §1.1 first.

Run the steps **in order**. Report each step's result as you go (✅/❌/skipped). On a hard failure, stop and report — don't continue into steps that depend on it.

## Step 1 — Detect repo root

```bash
git rev-parse --show-toplevel
```

Must be the `ignite` repo (check `OPERATIONS.md` exists at root). If not, stop: "Run /iiq-setup from inside the ignite repo clone."

## Step 2 — npm install

```bash
cd "$(git rev-parse --show-toplevel)" && npm install
```

Repo JS deps (screenshot harness etc.). If `npm` is not found, the node prerequisite is missing — stop and point at ONBOARDING.md §1.1 (`brew install gh node`).

## Step 3 — Playwright Chromium

```bash
npx playwright install chromium
```

~150 MB, one-time — needed by `/visual-iiq-diff`. Skips fast if already installed.

## Step 4 — `~/.ssh/config` WPE entries (ask first)

Check whether `~/.ssh/config` already contains a `Host` block matching `igniteiqstg.ssh.wpengine.net`. If present, skip. If missing, **ask the operator before writing**, then append (do not overwrite the file):

```
Host igniteiqstg.ssh.wpengine.net igniteiq.ssh.wpengine.net
  IdentityFile ~/.ssh/wpe_deploy_key
  IdentitiesOnly yes
  ServerAliveInterval 30
```

Note: the operator's key may live at `~/.ssh/id_ed25519` instead (ONBOARDING.md §1.4 default) — ask which path their WPE key uses and write that as `IdentityFile`. Never suggest copying another operator's key.

## Step 5 — gh auth + workflow scope

```bash
gh auth status
```

Verify authenticated AND scopes include `repo` and `workflow` (the `workflow` scope is what allows production deploy dispatch). If not authenticated or scope missing, guide:

```bash
gh auth login        # → GitHub.com → HTTPS → browser flow
gh auth refresh -s workflow   # if authenticated but missing the scope
```

## Step 6 — SSH no-op to both WPE envs

```bash
ssh -o BatchMode=yes -o ConnectTimeout=10 igniteiqstg@igniteiqstg.ssh.wpengine.net 'echo staging-ok'
ssh -o BatchMode=yes -o ConnectTimeout=10 igniteiq@igniteiq.ssh.wpengine.net 'echo prod-ok'
```

Both must print their ok line. On failure: the operator's public key probably isn't registered in the WPE User Portal yet (ONBOARDING.md §1.4) — report which env failed and stop here for SSH-dependent features (the rest of setup can continue).

## Step 7 — Starter `.claude/settings.local.json`

This file is per-operator and gitignored — never commit it. If it already exists, show the operator a diff against the template and ask before changing anything; otherwise write the template below. **Walk the operator through each `allow` entry and get explicit confirmation for every irreversible permission (commits, pushes, deploys, SSH) before including it** — drop any they decline. Keep the full `deny` list regardless.

```json
{
  "permissions": {
    "allow": [
      "Bash(git add*)", "Bash(git commit*)", "Bash(git pull*)",
      "Bash(git push origin main)", "Bash(git push origin main:*)",
      "Bash(gh workflow run deploy-staging.yml*)",
      "Bash(gh workflow run deploy-production.yml*)",
      "Bash(gh workflow run configure-mail.yml*)",
      "Bash(ssh igniteiqstg@igniteiqstg.ssh.wpengine.net*)",
      "Bash(ssh igniteiq@igniteiq.ssh.wpengine.net*)"
    ],
    "deny": [
      "Bash(git push --force*)", "Bash(git push -f*)",
      "Bash(git reset --hard*)", "Bash(gh secret set*)",
      "Bash(rm -rf*)"
    ]
  }
}
```

Rationale (tell the operator): read-only ops live in the committed `.claude/settings.json`; anything irreversible is opted into per-operator, per-machine — and the denies travel with it.

## Step 8 — Context check + summary

Run `/iiq-check-context` and print its green/red summary, plus a one-paragraph recap of which setup steps ran vs. skipped. End with: "Verify it worked — ask me to summarize the production wiring. A correct answer cites the SMTP mu-plugin, DKIM selector `google`, the DEPLOY-PRODUCTION confirm gate, and the FIDELITY EXCEPTION protocol."

## Don'ts

- Don't overwrite an existing `~/.ssh/config` — append only, and only after asking.
- Don't write `settings.local.json` permissions the operator hasn't explicitly confirmed.
- Don't run `brew install` — admin-password steps are the operator's (ONBOARDING.md §1).
- Don't commit anything. Setup touches no tracked files.
