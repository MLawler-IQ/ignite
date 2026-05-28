<?php if (!defined('ABSPATH')) exit; ?>
<div style="display:grid; grid-template-columns:220px 1fr; min-height:500px; font-family:system-ui, -apple-system, sans-serif;">
  <aside style="background:#171717; color:#ECECEC; padding:14px 10px; border-right:1px solid rgba(255,255,255,0.06);">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:6px 8px 14px;">
      <div style="display:flex; align-items:center; gap:10px;">
        <span style="width:26px; height:26px; border-radius:50%; background:#FFFFFF; color:#171717; display:inline-flex; align-items:center; justify-content:center; font-family:Georgia, serif; font-weight:800; font-size:14px;">&#10042;</span>
        <span style="font-size:14px; font-weight:600;">ChatGPT</span>
      </div>
      <span style="font-size:14px; opacity:0.5;">&#9998;</span>
    </div>

    <div style="padding:7px 10px; border-radius:8px; background:rgba(255,255,255,0.04); font-size:13px; color:rgba(236, 236, 236, 0.55); margin-bottom:18px; display:flex; align-items:center; gap:8px;">
      <span style="font-size:12px;">&#8981;</span> Search chats
    </div>

    <div style="padding:8px 10px; border-radius:8px; background:rgba(255,255,255,0.08); font-size:13px; color:#ECECEC; margin-bottom:6px; display:flex; align-items:center; gap:8px; font-weight:500;">
      <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
      Tech profitability &mdash; real
    </div>

    <div style="padding:7px 10px; font-size:13px; color:rgba(236, 236, 236, 0.65); border-radius:8px;">Service mix vs. callback rate</div>
    <div style="padding:7px 10px; font-size:13px; color:rgba(236, 236, 236, 0.65); border-radius:8px;">Truck inventory turnover</div>
    <div style="padding:7px 10px; font-size:13px; color:rgba(236, 236, 236, 0.65); border-radius:8px;">Maintenance plan renewals Q3</div>
    <div style="padding:7px 10px; font-size:13px; color:rgba(236, 236, 236, 0.65); border-radius:8px;">Dispatch heuristics review</div>

    <div style="margin-top:24px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.06); font-family:var(--font-mono); font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:rgba(236, 236, 236, 0.5); display:flex; align-items:center; gap:8px; padding:14px 10px 0;">
      <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
      IgniteIQ Connector
    </div>
  </aside>

  <div style="display:flex; flex-direction:column; background:#212121; color:#ECECEC;">
    <div style="padding:14px 22px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between;">
      <div style="display:inline-flex; align-items:center; gap:8px; padding:5px 12px; border-radius:8px; background:rgba(255,255,255,0.05); font-size:13px; font-weight:600;">
        GPT-5 <span style="font-size:10px; opacity:0.6;">&#9662;</span>
      </div>
    </div>

    <div style="padding:22px 22px 6px; display:flex; justify-content:flex-end;">
      <div style="max-width:78%; padding:12px 16px; border-radius:18px; background:#2F2F2F; font-size:14.5px; line-height:1.55; color:#ECECEC;">
        Which of my technicians are actually profitable? Don&rsquo;t show me average ticket &mdash; that lies. I want true contribution margin once callback costs and rework are factored in.
      </div>
    </div>

    <div style="padding:14px 22px 22px; display:flex; flex-direction:column; gap:12px;">
      <div style="display:flex; align-items:center; gap:10px; font-family:var(--font-mono); font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:rgba(236, 236, 236, 0.5);">
        <span style="width:20px; height:20px; border-radius:50%; background:#FFFFFF; color:#171717; display:inline-flex; align-items:center; justify-content:center; font-family:Georgia, serif; font-weight:800; font-size:11px;">&#10042;</span>
        ChatGPT
      </div>
      <div style="font-size:14.5px; line-height:1.6; color:#ECECEC;">
        Pulled from the IgniteIQ ontology &mdash; <b>YTD across 4,218 completed jobs</b>. Average ticket is misleading; here&rsquo;s true contribution margin after callbacks and rework:
      </div>

      <div style="border-radius:8px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); font-family:var(--font-mono); font-size:11.5px;">
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; background:rgba(255,255,255,0.04); padding:8px 12px; gap:8px; color:rgba(236, 236, 236, 0.6); letter-spacing:0.08em; text-transform:uppercase;">
          <span>Technician</span>
          <span style="text-align:right;">Avg ticket</span>
          <span style="text-align:right;">Callback</span>
          <span style="text-align:right;">True margin</span>
          <span style="text-align:right;">Read</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:rgba(76, 175, 80, 0.06);">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">Eric M.</span>
          <span style="text-align:right;">$1,684</span>
          <span style="text-align:right; color:#9DD9A0;">2.1%</span>
          <span style="text-align:right; color:#9DD9A0;">+$342K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:#9DD9A0;">Top performer</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:rgba(76, 175, 80, 0.06);">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">Marco D.</span>
          <span style="text-align:right;">$1,432</span>
          <span style="text-align:right; color:#9DD9A0;">3.4%</span>
          <span style="text-align:right; color:#9DD9A0;">+$268K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:#9DD9A0;">Strong</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:rgba(225, 29, 46, 0.10);">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">James R.</span>
          <span style="text-align:right;">$1,612</span>
          <span style="text-align:right; color:#FF8A8A;">9.8%</span>
          <span style="text-align:right; color:#FF8A8A;">+$118K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:#FF8A8A;">Looks good &mdash; isn&rsquo;t</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:transparent;">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">Tony S.</span>
          <span style="text-align:right;">$1,298</span>
          <span style="text-align:right; color:#ECECEC;">4.1%</span>
          <span style="text-align:right; color:#ECECEC;">+$224K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:rgba(236, 236, 236, 0.75);">Solid</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:rgba(225, 29, 46, 0.10);">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">Kevin P.</span>
          <span style="text-align:right;">$1,540</span>
          <span style="text-align:right; color:#FF8A8A;">11.2%</span>
          <span style="text-align:right; color:#FF8A8A;">+$84K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:#FF8A8A;">Bleeding margin</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 0.9fr 0.9fr 0.9fr 1.3fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#ECECEC; background:transparent;">
          <span style="font-family:system-ui, sans-serif; font-size:13px; font-weight:500;">Daryl B.</span>
          <span style="text-align:right;">$1,388</span>
          <span style="text-align:right; color:#ECECEC;">3.0%</span>
          <span style="text-align:right; color:#ECECEC;">+$251K</span>
          <span style="text-align:right; font-family:system-ui, sans-serif; font-size:12px; color:rgba(236, 236, 236, 0.75);">Solid</span>
        </div>
      </div>

      <div style="font-size:14.5px; line-height:1.6; color:#ECECEC; margin-top:4px;">
        The honest read: <b>James and Kevin look like top techs on the dispatch board</b> &mdash; high tickets, busy days. But once callback cost is factored in, James is making <i>half</i> what Eric is, and Kevin is barely contributing. Worth a coaching conversation, not a roster change.
      </div>

      <div style="margin-top:6px; padding:8px 12px; border-radius:6px; background:rgba(225, 29, 46, 0.06); border:1px solid rgba(225, 29, 46, 0.20); font-family:var(--font-mono); font-size:11px; letter-spacing:0.08em; color:rgba(236, 236, 236, 0.75); display:flex; align-items:center; gap:8px;">
        <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
        Grounded in IgniteIQ ontology &middot; 4,218 jobs &middot; 6 technicians &middot; YTD as of this morning
      </div>
    </div>

    <div style="margin-top:auto; padding:12px 22px 18px;">
      <div style="padding:12px 16px; border-radius:24px; background:#2F2F2F; border:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between; font-size:13.5px; color:rgba(236, 236, 236, 0.45);">
        <span>Ask anything about the business&hellip;</span>
        <span style="width:28px; height:28px; border-radius:50%; background:#FFFFFF; color:#171717; display:inline-flex; align-items:center; justify-content:center; font-size:14px; font-weight:700;">&uarr;</span>
      </div>
    </div>
  </div>
</div>
