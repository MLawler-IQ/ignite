<?php if (!defined('ABSPATH')) exit; ?>
<?php
// Ride-along page composition — ported from Demo.jsx DemoPage() (line 614+).
// Renders DemoHero + 7 Scenes (6 surface mockups + dashboard) + DemoClosing.
//
// FIDELITY EXCEPTION: dropped IntersectionObserver-driven scene reveal animations
//   (iiqDemoFade, iiqDemoWipe) — static PHP can't run them. Initial-state styles
//   (opacity:0, translateY) intentionally omitted so content is visible.
// FIDELITY EXCEPTION: dropped sticky <TimeRail/> sidebar (Demo.jsx:363) — requires
//   live scroll-position tracking via IntersectionObserver. Page reads top-down
//   without it; can be re-added as a client-side enhancement later.

$scenes = [
    [
        'time'   => '6:00 AM',
        'period' => 'Before the first truck leaves the yard',
        'tool'   => 'Gmail',
        'where'  => 'At his kitchen table. Coffee. The trucks are still cold in the yard.',
        'body'   => "Scott opens his inbox. The brief from IgniteIQ is already at the top — sent at 5:58 AM, before he was awake. Three things going right. Two things that need his attention. One decision he needs to make today. He reads it in three minutes. He knows what kind of day this is.",
        'mockup' => 'gmail',
    ],
    [
        'time'   => '8:47 AM',
        'period' => 'The morning rush',
        'tool'   => 'Slack',
        'where'  => 'At the shop. Mike, the ops lead, is already in #intelligence.',
        'body'   => "By the time Scott rolls into the shop, the morning rush is in full swing. Five trucks rolled out at 7:30. A sixth is finishing a tune-up across town. Mike, the ops lead, is already in the #intelligence channel — there's a route opportunity worth $2,400 today. Mike clicks reroute. Done. Moved.",
        'mockup' => 'slack',
    ],
    [
        'time'   => '10:30 AM',
        'period' => 'Weekly review with the CFO',
        'tool'   => 'Excel',
        'where'  => 'David’s office. The operating plan. Cell C3.',
        'body'   => "Scott walks into David's office for the weekly review. David — the CFO — opens the Q2 operating plan. Row 3, the install gross margin cell, is flagged red. Hovering it, IgniteIQ has already explained why: Yelp-sourced jobs running 6 points under target. Plus the path to close it. They don't have to ask. The platform already told them.",
        'mockup' => 'excel',
    ],
    [
        'time'   => '12:14 PM',
        'period' => 'On the road',
        'tool'   => 'iMessage',
        'where'  => 'Driving back from a customer site. Phone in the cupholder.',
        'body'   => "On the way back from a customer site, Scott's phone buzzes. Friday's revenue is going to miss plan by $18K unless they add two more bookings today. Two recovered leads from Tuesday were never called back. CSR-AI is ready to reach them in the next hour. Scott texts: “Yes, go.” Within ninety seconds, calls are dialing.",
        'mockup' => 'imessage',
    ],
    [
        'time'   => '1:30 PM',
        'period' => 'A hard question from marketing',
        'tool'   => 'Claude',
        'where'  => 'Sarah’s desk. The marketing dashboard she stopped trusting two years ago.',
        'body'   => "Sarah, head of marketing, has been asking the same question for six months: which channels are actually profitable, not just busy. She opens Claude. Types the question in plain English. The IgniteIQ connector reads the live ontology — 1,247 jobs, 612 leads, every dollar attributed. The answer: Yelp is losing money. The next budget shifts before the day ends.",
        'mockup' => 'claude',
    ],
    [
        'time'   => '3:00 PM',
        'period' => 'The afternoon debrief',
        'tool'   => 'ChatGPT',
        'where'  => 'Eric’s office. End-of-day service ops review.',
        'body'   => "Eric, the VP of service ops, pulls up ChatGPT to answer the question average ticket has always hidden: which techs are actually profitable? With the IgniteIQ connector, ChatGPT reads the resolved ontology, factors in callback costs, and ranks the real performers. Two techs the team thought were stars are bleeding margin. Not a roster change. A coaching conversation.",
        'mockup' => 'chatgpt',
    ],
    [
        'time'   => '4:00 PM',
        'period' => 'Daily standup in the kitchen',
        'tool'   => 'Daily dashboard',
        'where'  => 'In the shop kitchen. CEO, CFO, three techs around the TV. Coffee refills.',
        'body'   => "By 4 PM the trucks are rolling back in. Scott, David, and a handful of techs gather in the kitchen for the daily standup — same ritual every day. But today the dashboard on the wall TV tells a different kind of story. Every number on it is live, pulled from the ontology in real time. And tucked at the bottom: three big wins from the day, each one traceable back to a decision the team made hours ago. The dispatcher reroute. The CSR-AI callbacks. The budget shift. The dashboard doesn’t just report the day. It ties it together.",
        'mockup' => 'kitchen-tv-dashboard',
    ],
];

$cast = [
    ['Scott Reilly', 'CEO'],
    ['David Park',   'CFO'],
    ['Mike Vega',    'Operations Lead'],
    ['Sarah Chen',   'Head of Marketing'],
];
?>

<!-- ─── Demo hero ─────────────────────────────────────────────────── -->
<section style="padding:168px 32px 96px;background:var(--bg-canvas);border-bottom:1px solid var(--border-subtle);">
    <div style="max-width:1240px;margin:0 auto;">
        <div style="display:inline-flex;align-items:center;gap:10px;font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-500);font-weight:600;">
            <span style="width:6px;height:6px;border-radius:50%;background:var(--ignite-500);"></span>
            Live demo · ~5 minutes
        </div>

        <h1 style="font-family:var(--font-display);font-size:clamp(48px, 7.2vw, 112px);font-weight:600;letter-spacing:-0.045em;line-height:0.95;margin:32px 0 0;color:var(--fg-primary);text-wrap:balance;max-width:1280px;">
            A day with IgniteIQ.
            <span style="color:var(--fg-tertiary);display:block;">From the first coffee to the last truck back in the yard.</span>
        </h1>

        <div class="iiq-grid-split" style="margin-top:56px;display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:flex-start;">
            <p style="font-size:19px;line-height:1.6;color:var(--fg-primary);margin:0;font-weight:500;">
                Meet <strong>Scott Reilly</strong>, CEO of TAPPS HVAC. Six trucks. Four markets. One Tuesday.
            </p>
            <p style="font-size:17px;line-height:1.65;color:var(--fg-secondary);margin:0;">
                What follows is a real day from when his alarm goes off to when the trucks come back in. Six surfaces. Six decisions. Each one shows up at the moment of decision — in the tool he was already going to open. Scroll through it like it’s actually happening.
            </p>
        </div>

        <!-- Cast of characters strip -->
        <div class="iiq-grid-4" style="margin-top:64px;padding-top:32px;border-top:1px solid var(--border-default);display:grid;grid-template-columns:repeat(4, 1fr);gap:32px;">
            <?php foreach ($cast as [$name, $role]): ?>
                <div>
                    <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--fg-tertiary);"><?= esc_html($role) ?></div>
                    <div style="margin-top:8px;font-family:var(--font-display);font-size:20px;font-weight:600;letter-spacing:-0.02em;color:var(--fg-primary);"><?= esc_html($name) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Scroll cue -->
        <div style="margin-top:80px;display:flex;align-items:center;gap:12px;font-family:var(--font-mono);font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);">
            <span>Scroll to begin the day</span>
            <span style="font-size:16px;">↓</span>
        </div>
    </div>
</section>

<!-- ─── Scenes ──────────────────────────────────────────────────── -->
<?php foreach ($scenes as $i => $scene):
    $reversed = ($i % 2 === 1);
    $bg = ($i % 2 === 0) ? 'var(--bg-canvas)' : 'var(--bg-sunken)';
?>
<section id="scene-<?= (int) $i ?>" data-scene-idx="<?= (int) $i ?>" class="iiq-scene-section" style="padding:120px 32px;background:<?= $bg ?>;border-top:1px solid var(--border-subtle);">
    <div style="max-width:1320px;margin:0 auto;">
        <!-- Time + role banner -->
        <div style="display:inline-flex;align-items:baseline;gap:18px;padding-bottom:18px;border-bottom:2px solid var(--ink-1000);margin-bottom:56px;">
            <span style="font-family:var(--font-display);font-size:clamp(36px, 4.4vw, 60px);font-weight:700;letter-spacing:-0.03em;line-height:1;color:var(--fg-primary);"><?= esc_html($scene['time']) ?></span>
            <span style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:600;"><?= esc_html($scene['period']) ?></span>
            <span style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--ignite-500);font-weight:600;display:inline-flex;align-items:center;gap:8px;">
                <span style="width:5px;height:5px;border-radius:50%;background:var(--ignite-500);"></span>
                <?= esc_html($scene['tool']) ?>
            </span>
        </div>

        <div class="iiq-surface-row" style="display:grid;grid-template-columns:0.85fr 1.15fr;gap:80px;align-items:center;direction:<?= $reversed ? 'rtl' : 'ltr' ?>;">
            <!-- Narrative -->
            <div style="direction:ltr;">
                <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:600;margin-bottom:18px;">
                    · Where Scott is right now
                </div>
                <div style="font-family:var(--font-display);font-size:clamp(20px, 1.6vw, 26px);font-weight:500;letter-spacing:-0.02em;line-height:1.3;color:var(--fg-primary);margin-bottom:28px;font-style:italic;">
                    <?= esc_html($scene['where']) ?>
                </div>
                <p style="font-size:17px;line-height:1.7;color:var(--fg-secondary);margin:0;text-wrap:pretty;">
                    <?= esc_html($scene['body']) ?>
                </p>
            </div>

            <!-- Mockup (WindowFrame chrome host — ports IntelligenceSurfaces.jsx:87) -->
            <div style="direction:ltr;min-width:0;">
                <?php $wf_dark = in_array($scene['mockup'], ['slack', 'imessage', 'claude', 'chatgpt'], true); ?>
                <div style="width:100%;border-radius:12px;overflow:hidden;background:<?= $wf_dark ? '#1A1D21' : '#FFFFFF' ?>;border:<?= $wf_dark ? '1px solid rgba(255,255,255,0.08)' : '1px solid var(--border-default)' ?>;box-shadow:0 30px 60px -25px rgba(15, 15, 18, 0.18), 0 8px 20px -10px rgba(15, 15, 18, 0.08);display:flex;flex-direction:column;">
                    <div style="padding:12px 14px;background:<?= $wf_dark ? '#222529' : '#F5F5F4' ?>;border-bottom:<?= $wf_dark ? '1px solid rgba(255,255,255,0.06)' : '1px solid var(--border-subtle)' ?>;display:flex;align-items:center;gap:7px;">
                        <span style="width:11px;height:11px;border-radius:50%;background:#FF5F57;"></span>
                        <span style="width:11px;height:11px;border-radius:50%;background:#FEBC2E;"></span>
                        <span style="width:11px;height:11px;border-radius:50%;background:#28C840;"></span>
                    </div>
                    <?php get_template_part('template-parts/mockups/' . $scene['mockup']); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endforeach; ?>

<!-- ─── Closing scene ────────────────────────────────────────────── -->
<section style="padding:160px 32px;background:var(--bg-canvas);color:var(--fg-primary);border-top:1px solid var(--border-subtle);">
    <div style="max-width:1100px;margin:0 auto;text-align:center;">
        <div style="display:inline-flex;align-items:baseline;gap:18px;padding-bottom:18px;border-bottom:2px solid var(--ink-1000);margin-bottom:40px;">
            <span style="font-family:var(--font-display);font-size:clamp(36px, 4.4vw, 60px);font-weight:700;letter-spacing:-0.03em;line-height:1;color:var(--fg-primary);">5:42 PM</span>
            <span style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:600;">End of day</span>
        </div>
        <h2 style="font-family:var(--font-display);font-size:clamp(48px, 6.4vw, 96px);font-weight:600;letter-spacing:-0.045em;line-height:0.96;margin:0;color:var(--ink-50);text-wrap:balance;">
            The trucks are back in the yard.
            <span style="color:var(--fg-tertiary);display:block;">The trucks are back in the yard.
Scott’s home in time
for dinner.</span>
        </h2>
        <p style="margin:40px auto 0;font-size:19px;line-height:1.55;color:var(--fg-secondary);max-width:720px;">
            Intelligence shows up where the work already happens. Seven surfaces. Six teams.
            Forty-seven decisions sharper than they would have been yesterday. None of them
            required Scott to go find the answer. The intelligence found him in the
            applications he was already going to open anyway.
        </p>

        <div style="margin-top:56px;display:inline-flex;gap:12px;flex-wrap:wrap;justify-content:center;">
            <a href="<?= esc_url(home_url('/contact/')) ?>" style="font-size:15px;font-weight:500;padding:16px 28px;background:var(--ignite-500);color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:10px;border-radius:6px;box-shadow:0 0 0 1px oklch(57.5% 0.232 25 / 0.4), 0 12px 32px -4px oklch(57.5% 0.232 25 / 0.5);">See this with your data →</a>
            <a href="<?= esc_url(home_url('/how-it-works/')) ?>" style="font-size:15px;font-weight:500;padding:16px 28px;background:transparent;color:var(--fg-primary);text-decoration:none;border:1px solid var(--border-default);border-radius:6px;">How it works</a>
        </div>
    </div>
</section>
