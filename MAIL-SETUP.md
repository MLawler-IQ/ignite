# Contact-form email setup

How contact-form submissions on igniteiq.com get emailed, and the one-time
ops steps required to make delivery reliable.

## What the code already does

- **The form** (`/contact/`) posts to `admin-ajax.php` (`action=iiq_contact`),
  handled by `igniteiq/inc/contact-form.php` → `wp_mail()`.
- **Recipients** are read from the editable **Site Settings → "Contact form
  recipients"** field (ACF `contact_email`). It accepts one or more addresses
  separated by commas. It is **pre-filled** with:

  ```
  scott@igniteiq.com, matt@igniteiq.com, joshscott@igniteiq.com
  ```

  Edit it in WP Admin to change who gets notified — no code change needed.
  If the field is ever cleared/invalid, the handler falls back to those same
  three addresses (`iiq_contact_recipients()`), so the form is never left
  without a destination.
- **Delivery** is routed through Google Workspace SMTP by
  `igniteiq/inc/mail.php` — but only once the credentials below are defined.
  Until then, `wp_mail()` uses WP Engine's default mailer, which is unreliable
  for `@igniteiq.com` recipients (fails DMARC alignment → spam/dropped).

## One-time ops steps (do on BOTH staging and production)

### 1. Pick a sender identity (no new paid seat required)

You do **not** need a new Workspace seat. Best no-cost option: add
`notifications@igniteiq.com` as an **alias** on an existing user (Admin →
Users → the user → *Add alternate emails* — free). Authenticate SMTP (step 2)
as that existing user; set the visible `From` to the alias. DKIM signs for the
domain either way.

Alternatives: a dedicated user seat (tidiest, paid) or any existing shared/ops
account. **Avoid** using a real person's *primary* address as both the auth
account and the `From` — bounces and replies would land in their inbox, and
site mail breaks when their password/MFA rotates or they offboard.

Note the distinction used below: `IIQ_SMTP_USER` is the account you
**authenticate** as; `IIQ_MAIL_FROM` is the **visible sender** (the alias).
They need not be the same address.

### 2. Get an SMTP credential

Pick one:

- **Google Workspace SMTP relay** (`smtp-relay.gmail.com:587`) — Admin console →
  Apps → Google Workspace → Gmail → *Routing* → **SMTP relay service**. Add an
  entry; for WP Engine (no static outbound IP) choose **"Require SMTP
  Authentication"** and authenticate as the account that owns the alias (or a
  dedicated account). Limit:
  ~10,000 msgs/day. Preferred.
- **Gmail SMTP** (`smtp.gmail.com:587`) — enable 2-Step Verification on
  `notifications@igniteiq.com`, then generate an **App Password** and use it as
  the SMTP password. Limit: ~2,000 msgs/day. Simpler, fine for a contact form.

### 3. Add constants to `wp-config.php` (NOT committed)

On each WP Engine environment (staging + production), add:

```php
define('IIQ_SMTP_HOST',      'smtp-relay.gmail.com'); // or smtp.gmail.com
define('IIQ_SMTP_PORT',      587);
define('IIQ_SMTP_USER',      'realuser@igniteiq.com');  // account you AUTH as
define('IIQ_SMTP_PASS',      'xxxx-xxxx-xxxx-xxxx');   // app password / relay credential
define('IIQ_SMTP_SECURE',    'tls');
define('IIQ_MAIL_FROM',      'notifications@igniteiq.com'); // visible sender (alias)
define('IIQ_MAIL_FROM_NAME', 'IgniteIQ Website');
```

`inc/mail.php` activates SMTP + the From identity automatically once
`IIQ_SMTP_HOST` is defined.

### 4. Confirm DNS auth for igniteiq.com

Verify these exist for the domain (Workspace usually sets them up, but confirm):

- **SPF** TXT record includes `include:_spf.google.com`.
- **DKIM** enabled in Admin → Apps → Gmail → *Authenticate email* (publish the
  generated `google._domainkey` TXT record).
- **DMARC** TXT record at `_dmarc.igniteiq.com` (at least `p=none` to monitor).

### 5. Test

From WP Engine SSH (or WP Admin if a mail-test tool is installed):

```bash
wp eval 'var_dump( wp_mail("matt@igniteiq.com", "IIQ mail test", "hello") );'
```

Then submit the real form at `/contact/` and confirm the message arrives in
**all three** inboxes. Check headers show `spf=pass` and `dkim=pass` for
`igniteiq.com`.

> Note: pushing to `main` auto-deploys theme code to **staging** only.
> Production is a separate manual deploy, and these wp-config/DNS steps must be
> applied to the production environment too.
