---
name: iiq-verify-mail
description: End-to-end contact-form mail test for igniteiq.com — nonce scrape, backdated timestamp, marker injection, admin-ajax submit, inbox verification. Use after anything touching email (mail.php, contact-form.php, SMTP config, DNS), or when the user says "test the contact form", "verify mail", or invokes "/iiq-verify-mail".
---

# iiq-verify-mail

*Ritual: nonce-scrape + backdated timestamp + marker + inbox check is 5 fiddly steps. Get one wrong and you can't tell a broken mail path from a broken test.*

This is the recipe from OPERATIONS.md → "Submit a test contact form", plus a unique marker so the delivery can be found. The form's spam defenses require a real nonce (scraped from the rendered page, same cookie jar) and a `rendered_at` at least ~10s in the past — hence the backdating.

## Step 0 — Choose env

Ask (or infer from context): staging or production?
- Staging: `https://igniteiqstg.wpenginepowered.com`
- Production: `https://igniteiq.com`

Set `BASE` accordingly. Recipients: whatever ACF `contact_email` holds (default: scott@, matt@, joshscott@igniteiq.com).

## Step 1 — Submit with marker

```bash
BASE="https://igniteiqstg.wpenginepowered.com"   # or https://igniteiq.com
MARKER="iiq-mail-test-$(date +%Y%m%d-%H%M%S)"

NONCE=$(curl -s -c /tmp/jar "$BASE/contact/" | grep -oE 'name="nonce" value="[a-f0-9]+"' | head -1 | sed -E 's/.*value="([a-f0-9]+)".*/\1/')
[ -n "$NONCE" ] || { echo "FAIL: could not scrape nonce from $BASE/contact/"; exit 1; }

curl -s -b /tmp/jar -X POST "$BASE/wp-admin/admin-ajax.php" \
  --data-urlencode "action=iiq_contact" \
  --data-urlencode "nonce=$NONCE" \
  --data-urlencode "website=" \
  --data-urlencode "rendered_at=$(($(date +%s) - 30))" \
  --data-urlencode "first=Test" --data-urlencode "last=User" \
  --data-urlencode "email=test@example.com" --data-urlencode "company=Test" \
  --data-urlencode "topic=Other" --data-urlencode "message=$MARKER"

echo "Marker: $MARKER"
```

The ajax response should be a success JSON. A failure JSON here means the form/handler is broken before mail is even attempted — report the response body.

## Step 2 — Verify delivery

- **If the Gmail MCP is available:** wait ~30–60s, then search the inbox for the marker string. Found → delivery confirmed; report the `From:` (`notifications@igniteiq.com`) and recipients. Not found after ~2 min → escalate to step 3.
- **If not:** print the marker and tell the operator to search their inbox for it (`in:anywhere "<marker>"` catches spam-foldered mail).

## Step 3 — If it didn't arrive

Triage in this order (mail path in OPERATIONS.md → Email):
1. Ajax response was success but no mail → SMTP layer: was `configure-mail.yml` run for this env? Are the `IIQ_SMTP_*` constants present in `wp-content/mu-plugins/iiq-smtp.php`? (Never read the file's secret values — check existence only.)
2. App password rotated/revoked? → `/iiq-rotate-smtp-pass`.
3. Delivered but spam-foldered → check SPF/DKIM/DMARC still pass (`/iiq-publish-dns` list mode for the three TXT records).

## Don'ts

- Don't invent a different submit path — the nonce + cookie-jar + backdated `rendered_at` recipe is the documented one; anything else trips the spam defenses and produces false failures.
- Don't test production casually — real notification mail lands in three real inboxes. Say so before submitting to prod.
- Don't read or print SMTP secret values anywhere in this ritual.
