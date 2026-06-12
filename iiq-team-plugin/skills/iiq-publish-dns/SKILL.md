---
name: iiq-publish-dns
description: The only sanctioned path for Cloudflare DNS changes on the igniteiq.com zone — list/create/update TXT/A/CNAME with SPF/DKIM/DMARC presets, typed CHANGE-MX confirmation required for MX. Use for any DNS record work, or when the user says "update DNS", "add a TXT record", "change SPF", or invokes "/iiq-publish-dns".
---

# iiq-publish-dns

*Ritual: raw API calls against the zone are irreversible and unaudited; this wrapper is the only sanctioned path. Never edit DNS in the Cloudflare dashboard except in emergencies — and announce it if you do (OPERATIONS.md Don't-touch list).*

Zone: `igniteiq.com`, zone_id `5f6003a1d80fbbca4fb4e687e47c8ba3` (baked in — never ask for it). Commands per OPERATIONS.md → "Publish/update a DNS record".

## Token handling (strict)

1. Ask the operator to export `CLOUDFLARE_API_TOKEN` in their shell (scope: Zone DNS:Edit on `igniteiq.com`; fresh tokens at https://dash.cloudflare.com/profile/api-tokens). **Never store it, never commit it, never echo it.** Reference it only as `$CLOUDFLARE_API_TOKEN`.
2. If the token value itself was pasted into chat at any point: it is exposed. **At exit, always remind:** "If the token appeared in this chat, rotate it now — chat-exposed credentials are treated as compromised (OPERATIONS.md invariant 5)."

```bash
ZONE_ID=5f6003a1d80fbbca4fb4e687e47c8ba3
```

## Operations

### List (always do this first — see current state before changing it)

```bash
curl -s -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?type=TXT&name=_dmarc.igniteiq.com"
```

Adjust `type`/`name` params as needed; omit them to list everything. Show the operator the matching records (id, type, name, content, ttl, proxied) before any write.

### Create (POST)

```bash
curl -s -X POST -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
  --data '{"type":"TXT","name":"...","content":"...","ttl":3600}'
```

### Update (PUT to the record id from list)

```bash
curl -s -X PUT -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records/<record_id>" \
  --data '{"type":"TXT","name":"...","content":"...","ttl":3600}'
```

Supported types: **TXT, A, CNAME** (+ MX behind the gate below). Always check `"success": true` in the response and show the resulting record. Before any write, state the exact before → after and get an explicit yes.

## Mail-auth presets (names + current live values per OPERATIONS.md)

| Preset | type | name | content |
|---|---|---|---|
| SPF | TXT | `igniteiq.com` (@) | `v=spf1 include:_spf.google.com include:amazonses.com ~all` |
| DKIM | TXT | `google._domainkey.igniteiq.com` | `v=DKIM1; k=rsa; p=<key>` (selector `google`, Workspace-generated) |
| DMARC | TXT | `_dmarc.igniteiq.com` | `v=DMARC1; p=none; rua=mailto:...@dmarc-reports.cloudflare.net` |

DMARC tightening (`p=none` → `quarantine`/`reject`): refuse unless the operator confirms DMARC reports have shown clean alignment for ~2 weeks (OPERATIONS.md → DNS).

## MX changes — hard gate

**Refuse any MX create/update/delete unless the operator types exactly `CHANGE-MX`.** Broken MX = lost company mail, silently, for everyone. Before accepting the confirmation, state: current MX (`smtp.google.com` — the Google Workspace tenant), the proposed change, and the blast radius ("all inbound mail for igniteiq.com"). No `CHANGE-MX`, no MX call — not even with the operator saying "yes" in other words.

## Exit checklist

- Re-list the changed record(s) and show final state.
- Remind: rotate the token if it ever appeared in chat.
- If the change altered live mail/DNS wiring: OPERATIONS.md must be updated in the same change set (R1 rule).
