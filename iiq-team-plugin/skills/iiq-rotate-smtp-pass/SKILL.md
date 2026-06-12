---
name: iiq-rotate-smtp-pass
description: Guided rotation of the Gmail app password behind contact-form mail — new app password, gh secret update, configure-mail re-run on both envs with test sends, old password revocation. Use on the 6-month cadence, after any chat exposure, or when the user says "rotate the SMTP password" or invokes "/iiq-rotate-smtp-pass".
---

# iiq-rotate-smtp-pass

*Ritual: secret handling across 3 systems (Google, GitHub secrets, both WPE envs); a missed step silently breaks all contact-form mail. Cadence: every 6 months (parity-plan §8), or immediately after the password appears in any chat.*

Context: SMTP auth is `matt@igniteiq.com` + a Gmail **app password** (not the account password). The secret lives as repo Actions secret `IIQ_SMTP_PASS`; `configure-mail.yml` writes it into `wp-content/mu-plugins/iiq-smtp.php` on each env. Full wiring: OPERATIONS.md → Email.

Walk the operator through each step; confirm completion before moving on.

## Step 1 — Generate the new app password (operator, manual)

myaccount.google.com → Security → 2-Step Verification → App passwords → create one (16 chars). The account is the SMTP auth mailbox (currently `matt@igniteiq.com` — only that account's owner can do this step).

**Do not paste the password into this chat.** The standing rule (OPERATIONS.md invariant 5): any credential that appears in a chat window is treated as exposed and must be rotated — pasting it here would restart this ritual.

## Step 2 — Update the repo secret (operator, manual)

In the operator's own terminal (not via this agent — `gh secret set` is deny-listed for agents by design):

```bash
gh secret set IIQ_SMTP_PASS --repo MLawler-IQ/ignite
# paste the new app password at the prompt
```

Confirm with: `gh secret list --repo MLawler-IQ/ignite` (shows update timestamp only — secrets are rotate-only, never read).

## Step 3 — Re-configure BOTH envs with test sends

```bash
gh workflow run configure-mail.yml -f environment=staging -f send_test=true -f test_to=matt@igniteiq.com --repo MLawler-IQ/ignite
gh run watch $(gh run list --workflow=configure-mail.yml --repo MLawler-IQ/ignite --limit 1 --json databaseId --jq '.[0].databaseId') --repo MLawler-IQ/ignite --exit-status

gh workflow run configure-mail.yml -f environment=production -f send_test=true -f test_to=matt@igniteiq.com --repo MLawler-IQ/ignite
gh run watch $(gh run list --workflow=configure-mail.yml --repo MLawler-IQ/ignite --limit 1 --json databaseId --jq '.[0].databaseId') --repo MLawler-IQ/ignite --exit-status
```

Both envs, no exceptions — a half-rotation leaves one env sending with a soon-to-be-revoked password.

## Step 4 — Confirm both test mails arrived

Check the test inbox (Gmail MCP if available, else the operator checks manually). Both must arrive before step 5. Optionally run `/iiq-verify-mail` against each env for a full form-path check.

## Step 5 — Revoke the old app password (operator, manual)

Back at myaccount.google.com → App passwords → delete the old one. **This is the step everyone forgets** — until it's done, the compromised/stale credential still works.

## Done checklist

- [ ] New app password created
- [ ] `IIQ_SMTP_PASS` secret updated
- [ ] configure-mail run green: staging
- [ ] configure-mail run green: production
- [ ] Both test mails received
- [ ] Old app password revoked
