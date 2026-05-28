<?php if (!defined('ABSPATH')) exit; ?>
<div class="iiq-mk-shell" style="display:grid; grid-template-columns:220px 1fr; min-height:480px; font-family:system-ui, -apple-system, sans-serif;">
  <aside class="iiq-mk-aside" style="background:#16181C; color:#E8E6E1; padding:20px 16px; border-right:1px solid rgba(255,255,255,0.06);">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:22px;">
      <span style="width:28px; height:28px; border-radius:6px; background:#D97757; display:flex; align-items:center; justify-content:center; color:#FFFFFF; font-weight:800; font-size:16px; font-family:Georgia, serif;">&#8251;</span>
      <span style="font-size:15px; font-weight:600; letter-spacing:-0.01em;">Claude</span>
    </div>
    <div style="padding:8px 12px; border-radius:6px; background:rgba(217, 119, 87, 0.10); font-size:13px; color:#E8E6E1; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
      <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
      <span style="font-weight:600;">Marketing &mdash; Q2 attribution</span>
    </div>
    <div style="padding:7px 12px; font-size:13px; color:rgba(232, 230, 225, 0.65); border-radius:6px;">Q1 retro &amp; lessons</div>
    <div style="padding:7px 12px; font-size:13px; color:rgba(232, 230, 225, 0.65); border-radius:6px;">Tech utilization analysis</div>
    <div style="padding:7px 12px; font-size:13px; color:rgba(232, 230, 225, 0.65); border-radius:6px;">Membership funnel deep-dive</div>
    <div style="padding:7px 12px; font-size:13px; color:rgba(232, 230, 225, 0.65); border-radius:6px;">Pricing ladder review</div>

    <div style="position:relative; margin-top:28px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-family:var(--font-mono); font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:rgba(232, 230, 225, 0.5); display:flex; align-items:center; gap:8px;">
      <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
      IgniteIQ Connector
    </div>
  </aside>

  <div style="display:flex; flex-direction:column; background:#1F2125; color:#E8E6E1;">
    <div style="padding:14px 22px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between; font-size:13px; font-weight:500; color:rgba(232, 230, 225, 0.7);">
      <span>Marketing &mdash; Q2 attribution &middot; Sonnet 4.5</span>
    </div>

    <div style="padding:18px 22px 8px; display:flex; flex-direction:column; gap:6px;">
      <div style="font-family:var(--font-mono); font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:rgba(232, 230, 225, 0.5);">You &middot; 8:42 AM</div>
      <div style="font-size:15px; line-height:1.55; color:#E8E6E1;">
        Which marketing channels are <i>actually</i> profitable this quarter &mdash; once you account for closed revenue and gross margin, not just bookings?
      </div>
    </div>

    <div style="padding:16px 22px 22px; display:flex; flex-direction:column; gap:10px; border-top:1px solid rgba(255,255,255,0.04); margin-top:8px;">
      <div style="font-family:var(--font-mono); font-size:10px; letter-spacing:0.14em; text-transform:uppercase; color:rgba(232, 230, 225, 0.5); display:flex; align-items:center; gap:8px;">
        <span style="color:#D97757; font-family:Georgia, serif; font-weight:800; font-size:12px;">&#8251;</span>
        Claude
      </div>
      <div style="font-size:14.5px; line-height:1.6; color:#E8E6E1;">
        Based on the live ontology in your warehouse &mdash; <b>1,247 jobs</b> and <b>612 leads</b> across Q2 to date:
      </div>

      <div class="iiq-mk-table" style="border-radius:8px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); font-family:var(--font-mono); font-size:11.5px;">
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; background:rgba(255,255,255,0.04); padding:8px 12px; gap:8px; color:rgba(232, 230, 225, 0.6); letter-spacing:0.08em; text-transform:uppercase;">
          <span>Channel</span>
          <span style="text-align:right;">Spend</span>
          <span style="text-align:right;">Booked</span>
          <span style="text-align:right;">CAC</span>
          <span style="text-align:right;">Margin</span>
        </div>
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#E8E6E1; background:rgba(76, 175, 80, 0.06);">
          <span style="font-family:system-ui, sans-serif; font-size:13px;">Google Local Services</span>
          <span style="text-align:right;">$12,400</span>
          <span style="text-align:right;">$184,200</span>
          <span style="text-align:right; color:#9DD9A0;">$94</span>
          <span style="text-align:right; color:#9DD9A0;">36.2%</span>
        </div>
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#E8E6E1; background:transparent;">
          <span style="font-family:system-ui, sans-serif; font-size:13px;">Google PPC</span>
          <span style="text-align:right;">$18,200</span>
          <span style="text-align:right;">$201,800</span>
          <span style="text-align:right; color:#E8E6E1;">$142</span>
          <span style="text-align:right; color:#E8E6E1;">31.4%</span>
        </div>
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#E8E6E1; background:rgba(225, 29, 46, 0.10);">
          <span style="font-family:system-ui, sans-serif; font-size:13px;">Yelp</span>
          <span style="text-align:right;">$9,800</span>
          <span style="text-align:right;">$54,300</span>
          <span style="text-align:right; color:#FF8A8A;">$284</span>
          <span style="text-align:right; color:#FF8A8A;">24.1%</span>
        </div>
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#E8E6E1; background:transparent;">
          <span style="font-family:system-ui, sans-serif; font-size:13px;">Direct mail</span>
          <span style="text-align:right;">$6,400</span>
          <span style="text-align:right;">$71,200</span>
          <span style="text-align:right; color:#E8E6E1;">$118</span>
          <span style="text-align:right; color:#E8E6E1;">33.0%</span>
        </div>
        <div style="display:grid; grid-template-columns:1.4fr 0.8fr 1fr 0.8fr 0.9fr; padding:8px 12px; gap:8px; border-top:1px solid rgba(255,255,255,0.04); color:#E8E6E1; background:rgba(76, 175, 80, 0.06);">
          <span style="font-family:system-ui, sans-serif; font-size:13px;">Referral</span>
          <span style="text-align:right;">$0</span>
          <span style="text-align:right;">$118,400</span>
          <span style="text-align:right; color:#9DD9A0;">$0</span>
          <span style="text-align:right; color:#9DD9A0;">41.8%</span>
        </div>
      </div>

      <div style="font-size:14.5px; line-height:1.6; color:#E8E6E1; margin-top:4px;">
        The honest answer: <b>Yelp is unprofitable</b> at current margin. Google Local Services and Referral are doing the heavy lifting. I&rsquo;d reallocate $4.2K/wk from Yelp into Google Local &mdash; projected lift ~11 booked jobs/week.
      </div>

      <div style="margin-top:6px; padding:8px 12px; border-radius:6px; background:rgba(217, 119, 87, 0.06); border:1px solid rgba(217, 119, 87, 0.20); font-family:var(--font-mono); font-size:11px; letter-spacing:0.08em; color:rgba(232, 230, 225, 0.7); display:flex; align-items:center; gap:8px;">
        <span style="width:5px; height:5px; border-radius:50%; background:var(--ignite-500);"></span>
        Grounded in IgniteIQ ontology &middot; 1,247 jobs &middot; 612 leads &middot; live as of 4 min ago
      </div>
    </div>
  </div>
</div>
