<?php if (!defined('ABSPATH')) exit; ?>
<?php
// Kitchen TV dashboard — ported from Demo.jsx:17–198 (DashboardMockup).
// FIDELITY EXCEPTION: dropped `iiqPulse` infinite live-dot animation — animations
// don't translate to static PHP. Visual dot remains; pulse keyframe omitted.

$kpis = [
    ['label' => 'Revenue today', 'value' => '$42,840', 'delta' => '+18%'],
    ['label' => 'Jobs booked',   'value' => '23',      'delta' => '+5'],
    ['label' => 'Avg ticket',    'value' => '$1,862',  'delta' => '+6.3%'],
    ['label' => 'Gross margin',  'value' => '34.2%',   'delta' => '+2.6pts'],
];

$wins = [
    [
        'time'   => '8:51 AM',
        'title'  => 'Reroute paid off',
        'body'   => 'Tech #47 reroute (from this morning’s Slack alert) captured a high-margin install in 92122.',
        'amount' => '+$2,400',
    ],
    [
        'time'   => '12:38 PM',
        'title'  => 'CSR-AI recovered 2 leads',
        'body'   => 'Two missed leads from Tuesday were booked within 90 minutes of Scott’s approval. Both quoted membership.',
        'amount' => '+$8,400 booked',
    ],
    [
        'time'   => '2:14 PM',
        'title'  => 'Yelp budget reallocated',
        'body'   => 'Yelp cut $4.2K/wk → Google Local Services. Forecast: +11 booked jobs/wk.',
        'amount' => '+11 jobs/wk',
    ],
];
?>
<div style="width:100%;border-radius:14px;overflow:hidden;background:#0F1115;border:1px solid #20242B;box-shadow:0 30px 60px -25px rgba(15, 15, 18, 0.32), 0 8px 20px -10px rgba(15, 15, 18, 0.16);font-family:system-ui, -apple-system, sans-serif;color:#ECECEC;">
    <!-- Header -->
    <div style="padding:14px 22px;border-bottom:1px solid #20242B;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;">
            <span style="font-family:var(--font-display);font-size:18px;font-weight:700;letter-spacing:-0.02em;">TAPPS HVAC</span>
            <span style="font-family:var(--font-mono);font-size:11px;color:rgba(236,236,236,0.55);letter-spacing:0.14em;text-transform:uppercase;">Daily · Tue May 28</span>
        </div>
        <span style="display:inline-flex;align-items:center;gap:8px;font-family:var(--font-mono);font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-400);font-weight:700;padding:4px 10px;border-radius:999px;background:rgba(225, 29, 46, 0.10);border:1px solid rgba(225, 29, 46, 0.25);">
            <span style="width:6px;height:6px;border-radius:50%;background:var(--ignite-500);"></span>
            Live · Powered by IgniteIQ
        </span>
    </div>

    <!-- KPI row -->
    <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:1px;background:#20242B;">
        <?php foreach ($kpis as $k): ?>
            <div style="background:#0F1115;padding:20px 22px;">
                <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:rgba(236,236,236,0.5);"><?= esc_html($k['label']) ?></div>
                <div style="margin-top:10px;font-family:var(--font-display);font-size:clamp(28px, 2.6vw, 36px);font-weight:700;letter-spacing:-0.025em;line-height:1;color:#ECECEC;"><?= esc_html($k['value']) ?></div>
                <div style="margin-top:8px;font-family:var(--font-mono);font-size:11px;color:#9DD9A0;font-weight:600;">▲ <?= esc_html($k['delta']) ?> vs avg Tue</div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chart -->
    <div style="padding:22px 22px 18px;border-bottom:1px solid #20242B;">
        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
            <span style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:rgba(236,236,236,0.5);">Booked revenue · 6 AM → 4 PM</span>
            <span style="font-family:var(--font-mono);font-size:11px;color:#9DD9A0;font-weight:600;">Pacing 22% ahead</span>
        </div>
        <svg viewBox="0 0 800 100" style="width:100%;height:100px;display:block;">
            <line x1="0" y1="20" x2="800" y2="20" stroke="#20242B" stroke-width="1" />
            <line x1="0" y1="40" x2="800" y2="40" stroke="#20242B" stroke-width="1" />
            <line x1="0" y1="60" x2="800" y2="60" stroke="#20242B" stroke-width="1" />
            <line x1="0" y1="80" x2="800" y2="80" stroke="#20242B" stroke-width="1" />
            <polyline fill="none" stroke="var(--ignite-500)" stroke-width="2.5" points="0,82 80,70 160,52 200,42 280,38 320,28 400,32 480,22 560,30 640,18 720,12 800,8" />
            <circle cx="80"  cy="70" r="3.5" fill="var(--ignite-500)" />
            <circle cx="200" cy="42" r="3.5" fill="var(--ignite-500)" />
            <circle cx="320" cy="28" r="3.5" fill="var(--ignite-500)" />
            <circle cx="480" cy="22" r="3.5" fill="var(--ignite-500)" />
            <circle cx="640" cy="18" r="3.5" fill="var(--ignite-500)" />
            <circle cx="800" cy="8"  r="3.5" fill="var(--ignite-500)" />
            <polyline fill="none" stroke="rgba(236,236,236,0.30)" stroke-width="1.5" stroke-dasharray="4 4" points="0,86 100,78 200,68 300,60 400,52 500,44 600,38 700,32 800,28" />
        </svg>
        <div style="margin-top:8px;display:flex;gap:18px;font-family:var(--font-mono);font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:rgba(236,236,236,0.5);">
            <span style="display:inline-flex;align-items:center;gap:6px;">
                <span style="width:14px;height:2px;background:var(--ignite-500);"></span> Today
            </span>
            <span style="display:inline-flex;align-items:center;gap:6px;">
                <span style="width:14px;height:2px;background:rgba(236,236,236,0.30);"></span> Avg Tuesday
            </span>
        </div>
    </div>

    <!-- 3 Big Wins -->
    <div style="padding:20px 22px 24px;">
        <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--ignite-400);font-weight:700;margin-bottom:16px;display:inline-flex;align-items:center;gap:8px;">
            <span style="width:6px;height:6px;border-radius:50%;background:var(--ignite-500);"></span>
            3 Big Wins Today
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($wins as $w): ?>
                <div style="display:grid;grid-template-columns:78px 1fr 140px;gap:16px;align-items:center;padding:14px 16px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:6px;">
                    <span style="font-family:var(--font-mono);font-size:11px;color:rgba(236,236,236,0.55);"><?= esc_html($w['time']) ?></span>
                    <div>
                        <div style="font-family:var(--font-display);font-size:15px;font-weight:600;letter-spacing:-0.015em;color:#ECECEC;"><?= esc_html($w['title']) ?></div>
                        <div style="margin-top:4px;font-size:12.5px;line-height:1.5;color:rgba(236,236,236,0.65);"><?= esc_html($w['body']) ?></div>
                    </div>
                    <span style="font-family:var(--font-display);font-size:18px;font-weight:700;letter-spacing:-0.02em;color:var(--ignite-400);text-align:right;"><?= esc_html($w['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
