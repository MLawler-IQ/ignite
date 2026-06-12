---
name: iiq-env-diff
description: Compare the managed ACF Site Settings options between staging and production WPE environments — fetch, normalize, diff, per-key report. Use when the user asks "does staging match prod?", "env drift", "compare settings", or invokes "/iiq-env-diff".
---

# iiq-env-diff

*Ritual: cross-env comparison with normalization that's fiddly to redo by hand. Once content is editable in WP admin, staging and prod `wp_options` diverge silently — this makes the drift visible.*

Scope: the managed `options_*` keys (ACF Site Settings values, e.g. `options_contact_email`). These survive deploys (`seed --force` never touches options — OPERATIONS.md "what seed --force overwrites"), so drift here is real operator-made divergence, not deploy noise. Cross-ref parity-plan §7 for the planned nightly variant.

## Step 1 — Fetch both envs

```bash
ssh igniteiqstg@igniteiqstg.ssh.wpengine.net \
  'cd sites/igniteiqstg && wp option list --search="options_*" --format=json' > /tmp/iiq-options-staging.json

ssh igniteiq@igniteiq.ssh.wpengine.net \
  'cd sites/igniteiq && wp option list --search="options_*" --format=json' > /tmp/iiq-options-prod.json
```

If either SSH fails: stop, point at `/iiq-check-context` check 2.

## Step 2 — Normalize

Raw `wp option list` output ordering is not stable and values may be PHP-serialized. Normalize both files to sorted `key<TAB>value` lines:

```bash
python3 - <<'PY'
import json
for env in ("staging", "prod"):
    rows = json.load(open(f"/tmp/iiq-options-{env}.json"))
    with open(f"/tmp/iiq-options-{env}.norm", "w") as f:
        for r in sorted(rows, key=lambda r: r["option_name"]):
            # skip ACF's internal field-key reference rows; compare values only
            if r["option_name"].startswith("_options_"):
                continue
            f.write(f"{r['option_name']}\t{r['option_value']}\n")
PY
```

(`_options_*` rows hold ACF field-key references — they differ only when the field schema itself changed, which is a deploy concern, not content drift. Note in the report if their counts differ.)

## Step 3 — Diff + per-key report

```bash
diff /tmp/iiq-options-staging.norm /tmp/iiq-options-prod.norm
```

Report per key, human-readable:

```
ENV DIFF — options_* (staging vs production), <date>
  options_contact_email     MATCH
  options_footer_columns    DRIFT
    staging: <value>
    prod:    <value>
  options_new_field         STAGING ONLY (missing on prod)
Verdict: <n> keys compared, <m> drifted
```

## Step 4 — Interpret, don't auto-fix

For each drifted key, say which direction is probably correct (staging is usually the testbed; prod is usually the truth for live content) and how to fix: WP admin → Site Settings on the env that's wrong, or `update_field()` via `wp eval-file`. **Never** `wp option update options_*` directly — it skips ACF's field-key reference (OPERATIONS.md Don't-touch list). Don't apply any fix without explicit operator confirmation.
