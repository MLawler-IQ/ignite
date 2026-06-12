---
name: iiq-purge-cf-cache
description: Per-URL Cloudflare edge purge for igniteiq.com with the right zone_id — instant invalidation after a deploy instead of waiting out the 10-minute edge TTL. Use when a change is live at origin but stale at the edge, after any visible prod deploy, or when the user says "purge the cache", "still seeing old content", or invokes "/iiq-purge-cf-cache".
---

# iiq-purge-cf-cache

*Ritual: per-URL purge with the right zone_id; "Purge Everything" is the tempting wrong answer — it cold-starts the whole edge for a one-page change.*

Context (OPERATIONS.md → Cache layers): deploys flush the WPE page cache but **not** the Cloudflare edge, which serves stale HTML up to ~10 min (`max-age=600`). This purges exactly the URLs that changed.

## Step 1 — Decide the URL list

Default: `https://igniteiq.com/` plus the specific pages this session changed. Ask the operator to confirm the list if it isn't obvious from context. Cornerstone path reference: `/`, `/how-it-works/`, `/ontology/`, `/company/`, `/contact/`, `/signin/`.

## Step 2 — Purge via API (preferred)

Requires `CLOUDFLARE_API_TOKEN` in the operator's shell (Zone Cache Purge or Zone DNS Edit scope covers it). Never store or echo the token.

```bash
ZONE_ID=5f6003a1d80fbbca4fb4e687e47c8ba3
curl -s -X POST -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/purge_cache" \
  --data '{"files":["https://igniteiq.com/","https://igniteiq.com/contact/"]}'
```

Edit the `files` array to the confirmed list. Check `"success": true` in the response.

## Step 2-alt — No token? Dashboard clickpath

dash.cloudflare.com → zone `igniteiq.com` → **Caching → Configuration → Purge Cache → Custom Purge** → paste the URL list → Purge. Tell the operator NOT to use "Purge Everything".

## Step 3 — Verify

```bash
curl -s -o /dev/null -w '%{http_code} cf-cache-status:%{header{cf-cache-status}}\n' "https://igniteiq.com/"
```

(Or `curl -sI https://igniteiq.com/ | grep -i cf-cache-status`.) Expect `MISS` or `EXPIRED` on the first hit after purge, then confirm the fresh content is visible. If still stale: the browser cache is the remaining layer — hard refresh (`⌘+Shift+R`).

## Don'ts

- Don't "Purge Everything" — per-URL only.
- Don't purge as a substitute for verifying the origin: if `curl -s "https://igniteiq.com/?v=$(date +%s)"` shows old content, the problem is the deploy, not the cache.
- Don't store the token; remind the operator to rotate it if it appeared in chat.
