# ONBOARDING.md — Operator setup for igniteiq.com

> Audience: a new operator (Scott) going from "Mac with nothing installed" to shipping site changes through Claude Code with full parity to Matt's setup.
> Time: ~10 min of manual steps (§1) + ~5 min automated (§2), most of it download time.
>
> **Status note:** §2 references the `iiq-team` plugin. Until the plugin ships (spec: `.planning/iiq-team-plugin-spec.md`), pair with Matt for first setup — §1 is accurate today regardless.

---

## 1. One-time manual prerequisites (~10 min)

These are irreducibly manual — each involves a password, 2FA, or an account that must be **yours** (never share credentials; per-person accounts keep the audit trail honest).

### 1.1 Install the tools

```bash
# Homebrew first if missing — https://brew.sh
brew install gh node
```

Install **Claude Code**: https://claude.com/claude-code → download → drag to Applications. (CLI alternative: `npm install -g @anthropic-ai/claude-code`.)

### 1.2 Anthropic account

Open Claude Code → sign in with **your own** Anthropic account. Matt provisions your seat — ping him if login is rejected.

### 1.3 GitHub

1. Accept the repo invite for `MLawler-IQ/ignite` (check your email).
2. Authenticate the CLI:

```bash
gh auth login        # → GitHub.com → HTTPS → browser flow
gh auth status       # verify scopes include: repo, workflow
```

The `workflow` scope is required — it's what lets you trigger production deploys.

### 1.4 WP Engine SSH key (generate your own)

```bash
ssh-keygen -t ed25519 -C "you@igniteiq.com"    # accept the default path
cat ~/.ssh/id_ed25519.pub                       # copy the output
```

Then: **my.wpengine.com → your profile → SSH Keys → Add key** → paste. This attaches WPE's server-access audit trail to *you*, distinct from Matt.

> Don't copy Matt's `wpe_deploy_key`. Same access, but the logs can no longer tell you apart — and revoking one person means revoking both.

### 1.5 Confirm pre-existing access

- **Google Workspace admin**: admin.google.com loads for you (only needed for mail-identity work — see OPERATIONS.md → Email)
- **Cloudflare**: dash.cloudflare.com shows the `igniteiq.com` zone (only needed for DNS work)

---

## 2. One-click Claude Code parity (~5 min, mostly downloads)

```bash
gh repo clone MLawler-IQ/ignite && cd ignite
claude plugin marketplace add MLawler-IQ/ignite
claude plugin install iiq-team@ignite
claude
```

Then, inside the session:

```
/iiq-setup
```

What `/iiq-setup` does (idempotent — safe to re-run anytime to revalidate):

| Step | Detail |
|---|---|
| `npm install` | repo JS deps (screenshot harness etc.) |
| `npx playwright install chromium` | ~150 MB, one-time — needed by `/visual-iiq-diff` |
| `~/.ssh/config` | writes the WPE host entries (asks before writing) so plain `ssh` works against both envs |
| gh auth check | confirms you're authenticated with `workflow` scope |
| SSH reachability | no-op command against staging + prod, confirms your key works |
| Starter settings | writes your `.claude/settings.local.json` from the documented template (you confirm each irreversible permission explicitly) |
| Context check | summarizes the live wiring from OPERATIONS.md so you know it loaded |

**Verify it worked:** ask the session *"summarize the production wiring"*. A correct answer cites: the SMTP mu-plugin at `wp-content/mu-plugins/iiq-smtp.php`, DKIM selector `google`, the `DEPLOY-PRODUCTION` confirm gate, and the FIDELITY EXCEPTION protocol. If it doesn't, `OPERATIONS.md` didn't auto-load — check you're inside `~/ignite`.

---

## 3. First-edit walkthrough — replay a real change

This replays commit `9b50a47` (shipped 2026-06-12) so you see the full loop on a change that actually happened.

### 3.1 What you type

Just describe the change in plain English:

```
On the contact page, remove the offices and the direct email/phone —
replace with "Headquartered in San Diego, CA." Also add a Sign In link
under Platform in the footer pointing to https://studio.igniteiq.com/#/signin
(new tab), and a Marketing Portal link under Resources pointing to
portal.igniteiq.com (new tab).
```

No skill invocation needed — ad-hoc edits go straight to the agent.

### 3.2 What the agent does (watch for each step)

1. **Reads** `igniteiq/template-parts/forms/contact.php` and `igniteiq/template-parts/footer.php`
2. **Edits** both, adding a `// FIDELITY EXCEPTION:` comment where the change diverges from the design export
3. **Lints** each modified file with `php -l` — must pass before any commit
4. **Pulls** (`git pull --rebase origin main`) then **commits + pushes** with a concise message
5. **Staging deploys automatically** (~60s) — the agent watches the run with `gh run watch`
6. **Verifies** the rendered HTML on `igniteiqstg.wpenginepowered.com`

### 3.3 You review staging

Open https://igniteiqstg.wpenginepowered.com/contact/ and the homepage footer. Confirm visually. For anything visual, also run:

```
/visual-iiq-diff
```

### 3.4 Ship to production

```
/iiq-deploy-prod
```

This walks the gate ritual: cornerstone hand-edit check → backup reminder → dispatch `deploy-production.yml -f confirm=DEPLOY-PRODUCTION` → watch → verify all 6 URLs return 200 → prompt you to purge the Cloudflare cache if the change is visible.

### 3.5 The one "failure" you'll definitely see

You check igniteiq.com after a successful deploy and **still see old content**. That's not a failed deploy — it's the Cloudflare edge cache (serves stale HTML up to ~10 min, `max-age=600`). Three fixes, any one works:

1. Hard refresh: `⌘ + Shift + R`
2. Wait ~10 min
3. `/iiq-purge-cf-cache` for instant invalidation

To confirm the origin is actually correct regardless of cache:

```bash
curl -s "https://igniteiq.com/?v=$(date +%s)" | grep -o "Headquartered in San Diego"
```

---

## 4. When stuck

### Workflow skills, one line each

| Skill | When |
|---|---|
| `/iiq-check-context` | Start of any session — verifies auth, SSH, secrets, clean tree |
| `/iiq-deploy-prod` | Shipping to production (always use this, never raw `gh workflow run`) |
| `/verify-iiq-fidelity` | After any deploy — confirms every export string renders |
| `/visual-iiq-diff` | After any visible change — screenshot comparison, all 6 pages |
| `/iiq-env-diff` | "Does staging match prod?" for editable settings |
| `/iiq-verify-mail` | After anything touching email — test contact-form delivery |
| `/iiq-purge-cf-cache` | Change is live at origin but stale at the edge |
| `/iiq-rotate-smtp-pass` | Mail credential rotation (guided) |
| `/iiq-publish-dns` | Any DNS record change (never raw API/dashboard) |
| `/iiq-port-design <url>` | A Claude Design handoff URL dropped — full port loop |
| `/iiq-setup` | Anything feels broken — re-run, it re-validates everything |

### Where answers live

- **"How does X work in production?"** → `OPERATIONS.md` (auto-loaded; sections: Environments, Deploy flow, Email, DNS, Secrets, Don't-touch list, Recipes, Cache layers)
- **"How does the theme/port loop work?"** → `CLAUDE.md` (cliff notes) and `AGENTS.md` (manual)
- **"What are the team conventions?"** → `.planning/parity-plan.md`

### Self-unblock checklist (before pinging Matt)

1. Re-run `/iiq-check-context` — most "it's broken" is a stale auth or unreachable SSH
2. Check the Actions tab: https://github.com/MLawler-IQ/ignite/actions — is a deploy red?
3. Cache? (§3.5 — the most common false alarm)
4. Ask the agent to read the relevant OPERATIONS.md section out loud

### Ping Matt anyway when

- Anything touching `inc/mail.php`, `inc/contact-form.php`, DNS, or secrets (these paths want his review regardless)
- A deploy failed AND re-running it failed
- You're about to do something OPERATIONS.md's Don't-touch list warns about
