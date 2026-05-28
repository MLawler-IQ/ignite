<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/_helpers.php';

// Intelligence Surfaces — host for the homepage "Intelligence finds you. You don't have to find it."
// section. Ported from IntelligenceSurfaces.jsx:1099–1262 (IntelligenceSurfacesSection).
//
// FIDELITY EXCEPTION: dropped <Reveal> wrappers (scroll-fade animation) — purely
//   presentational, not load-bearing for content.
// FIDELITY EXCEPTION: dropped the "Play the demo" interactive walk-through hooks
//   referenced upstream in IntelligenceSurfaces.jsx; this partial renders the
//   static zigzag layout only.

$eyebrow_default       = 'Headless by design';
$headline_lead_default = 'Intelligence finds you.';
$headline_gap_default  = 'You don’t have to find it.';
$body_default          = 'Intelligence isn’t valuable if it lives somewhere your team has to go visit. It’s valuable when it shows up at the moment of decision, in the surface where the work is already happening.';

$surfaces_default = [
    [
        'tool'    => 'Slack',
        'role'    => 'Operations lead',
        'heading' => 'In the channel where the work is already happening.',
        'body'    => "The dispatcher's day runs in Slack. So the intelligence finds them there — an alert in their operations channel with the one decision worth making this hour, and a button to make it. No new app to learn. No dashboard to remember to open.",
        'mockup'  => 'slack',
    ],
    [
        'tool'    => 'Gmail',
        'role'    => 'CEO',
        'heading' => 'In the inbox you already open at 6 AM.',
        'body'    => "Most CEOs start the day in their inbox. So the morning brief lands there — three things going right, two needing attention, the one decision that actually matters today. Read once, act, move on. Not a dashboard you'll open later.",
        'mockup'  => 'gmail',
    ],
    [
        'tool'    => 'Excel',
        'role'    => 'CFO',
        'heading' => 'In the spreadsheet your CFO actually trusts.',
        'body'    => "Finance lives in Excel. The intelligence shows up as a live cell comment — not a slide, not a chart, not a separate report. The variance, the driver, the recommended move, traceable back to the underlying data. Right next to the number that prompted the question.",
        'mockup'  => 'excel',
    ],
    [
        'tool'    => 'iMessage',
        'role'    => 'CEO',
        'heading' => 'In the text thread you actually read.',
        'body'    => "Some moments aren't for an email or a dashboard. A miss on Friday's revenue. A lead the team forgot. The platform texts the CEO with the specific issue and a one-tap action — and reports back when it's done.",
        'mockup'  => 'imessage',
    ],
    [
        'tool'    => 'Claude',
        'role'    => 'Head of marketing',
        'heading' => 'In the AI you already work with.',
        'body'    => "Marketing wants to ask hard questions in plain English. Through the IgniteIQ connector, Claude reads the resolved ontology directly — every job, every lead, every dollar — and gives a real answer, grounded in your live data, not a guess.",
        'mockup'  => 'claude',
    ],
    [
        'tool'    => 'ChatGPT',
        'role'    => 'Head of service operations',
        'heading' => 'In the AI your team already opens every day.',
        'body'    => "Service ops needs to cut through the surface and see what's actually true. Through the IgniteIQ connector, ChatGPT reads the resolved ontology directly and answers the hard questions — which technicians are really profitable, which jobs look good and aren't, where the margin is hiding. Plain English in, real answers out.",
        'mockup'  => 'chatgpt',
    ],
];

// ACF sub-field fallbacks
$eyebrow       = (function_exists('get_sub_field') ? (get_sub_field('eyebrow') ?: '') : '') ?: $eyebrow_default;
$headline_lead = (function_exists('get_sub_field') ? (get_sub_field('headline_lead') ?: '') : '') ?: $headline_lead_default;
$headline_gap  = (function_exists('get_sub_field') ? (get_sub_field('headline_gap') ?: '') : '') ?: $headline_gap_default;
$body          = (function_exists('get_sub_field') ? (get_sub_field('body') ?: '') : '') ?: $body_default;

$surfaces_acf = function_exists('get_sub_field') ? get_sub_field('surfaces') : null;
$surfaces     = (is_array($surfaces_acf) && !empty($surfaces_acf)) ? $surfaces_acf : $surfaces_default;
?>
<section data-reveal class="iiq-pad iiq-section-pad">
    <div style="position:relative;max-width:1320px;margin:0 auto;">
        <?php if (function_exists('iiq_section_marker')) iiq_section_marker(); ?>

        <?php if (function_exists('iiq_section_eyebrow')) {
            iiq_section_eyebrow($eyebrow);
        } else { ?>
            <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-500);font-weight:600;">
                <?= esc_html($eyebrow) ?>
            </div>
        <?php } ?>

        <h2 style="font-family:var(--font-display);font-size:clamp(40px, 5.6vw, 76px);font-weight:600;letter-spacing:-0.04em;line-height:0.98;margin:24px 0 0;color:var(--fg-primary);max-width:1180px;text-wrap:balance;">
            <?= wp_kses_post($headline_lead) ?><span style="color:var(--fg-tertiary);"> <?= wp_kses_post($headline_gap) ?></span>
        </h2>

        <?php if ($body): ?>
            <p style="margin-top:28px;max-width:820px;font-size:18px;line-height:1.55;color:var(--fg-secondary);text-wrap:pretty;">
                <?= wp_kses_post($body) ?>
            </p>
        <?php endif; ?>

        <div style="margin-top:96px;display:flex;flex-direction:column;gap:96px;">
            <?php foreach ($surfaces as $i => $surface):
                $reversed = ($i % 2 === 1);
                $tool    = isset($surface['tool'])    ? $surface['tool']    : '';
                $role    = isset($surface['role'])    ? $surface['role']    : '';
                $heading = isset($surface['heading']) ? $surface['heading'] : '';
                $sbody   = isset($surface['body'])    ? $surface['body']    : '';
                $mockup  = isset($surface['mockup'])  ? $surface['mockup']  : '';
                $step    = '0' . ($i + 1);
            ?>
                <div style="display:grid;grid-template-columns:0.85fr 1.15fr;gap:80px;align-items:center;direction:<?= $reversed ? 'rtl' : 'ltr' ?>;">
                    <div style="direction:ltr;">
                        <div style="display:inline-flex;align-items:center;gap:10px;font-family:var(--font-mono);font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:500;">
                            <span style="font-family:var(--font-display);font-weight:700;font-size:14px;color:var(--fg-primary);letter-spacing:0;text-transform:none;"><?= esc_html($step) ?></span>
                            <span style="width:24px;height:1px;background:var(--border-default);"></span>
                            <span><?= esc_html($tool) ?></span>
                        </div>
                        <h3 style="font-family:var(--font-display);font-size:clamp(26px, 2.6vw, 36px);font-weight:600;letter-spacing:-0.025em;line-height:1.15;margin:20px 0 16px;color:var(--fg-primary);text-wrap:balance;">
                            <?= esc_html($heading) ?>
                        </h3>
                        <div style="font-family:var(--font-mono);font-size:10.5px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-500);font-weight:500;margin-bottom:14px;display:inline-flex;align-items:center;gap:8px;">
                            <span style="width:5px;height:5px;border-radius:50%;background:var(--ignite-500);"></span>
                            For the <?= esc_html($role) ?>
                        </div>
                        <p style="font-size:16px;line-height:1.6;color:var(--fg-secondary);margin:0;text-wrap:pretty;max-width:520px;">
                            <?= esc_html($sbody) ?>
                        </p>
                    </div>
                    <div style="direction:ltr;min-width:0;">
                        <?php
                        // WindowFrame chrome — ports IntelligenceSurfaces.jsx
                        // WindowFrame helper (line 87). Edge-to-edge mockup
                        // with a macOS-style traffic-lights header bar. The
                        // mockup partials emit their own inner background, so
                        // this wrapper provides only the outer chrome.
                        $wf_dark = in_array($mockup, ['slack', 'imessage', 'claude', 'chatgpt'], true);
                        ?>
                        <div style="width:100%;border-radius:12px;overflow:hidden;background:<?= $wf_dark ? '#1A1D21' : '#FFFFFF' ?>;border:<?= $wf_dark ? '1px solid rgba(255,255,255,0.08)' : '1px solid var(--border-default)' ?>;box-shadow:0 30px 60px -25px rgba(15, 15, 18, 0.18), 0 8px 20px -10px rgba(15, 15, 18, 0.08);display:flex;flex-direction:column;">
                            <div style="padding:12px 14px;background:<?= $wf_dark ? '#222529' : '#F5F5F4' ?>;border-bottom:<?= $wf_dark ? '1px solid rgba(255,255,255,0.06)' : '1px solid var(--border-subtle)' ?>;display:flex;align-items:center;gap:7px;">
                                <span style="width:11px;height:11px;border-radius:50%;background:#FF5F57;"></span>
                                <span style="width:11px;height:11px;border-radius:50%;background:#FEBC2E;"></span>
                                <span style="width:11px;height:11px;border-radius:50%;background:#28C840;"></span>
                            </div>
                            <?php if ($mockup) get_template_part('template-parts/mockups/' . $mockup); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Walk-a-day teaser -->
        <div style="margin-top:96px;padding:36px 40px;border:1px solid var(--border-default);background:var(--bg-canvas);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:32px;flex-wrap:wrap;">
            <div style="max-width:720px;">
                <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-500);margin-bottom:10px;display:inline-flex;align-items:center;gap:8px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--ignite-500);"></span>
                    See a full day
                </div>
                <h3 style="font-family:var(--font-display);font-size:clamp(24px, 2.4vw, 32px);font-weight:600;letter-spacing:-0.025em;line-height:1.2;margin:0;color:var(--fg-primary);">
                    Walk a day with an operator running on IgniteIQ.
                </h3>
                <p style="margin:12px 0 0;font-size:16px;line-height:1.55;color:var(--fg-secondary);">
                    6:00 AM to 5:00 PM. Six surfaces. One Tuesday. Scroll through it like it's actually happening.
                </p>
            </div>
            <a href="<?= esc_url(home_url('/ride-along/')) ?>" style="font-size:15px;font-weight:500;padding:14px 26px;background:var(--ignite-500);color:var(--ink-50);text-decoration:none;border-radius:6px;display:inline-flex;align-items:center;gap:10px;box-shadow:0 0 0 1px rgba(225, 29, 46, 0.3), 0 12px 32px -4px rgba(225, 29, 46, 0.45);">Watch a day →</a>
        </div>

        <!-- Closing line -->
        <div style="margin-top:96px;padding-top:56px;border-top:1px solid var(--border-default);display:flex;flex-direction:column;align-items:center;text-align:center;">
            <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--fg-tertiary);margin-bottom:24px;display:flex;align-items:center;gap:12px;">
                <span style="width:24px;height:1px;background:var(--border-default);"></span>
                <span>The point</span>
                <span style="width:24px;height:1px;background:var(--border-default);"></span>
            </div>
            <h3 style="margin:0;max-width:920px;font-family:var(--font-display);font-size:clamp(28px, 3.4vw, 44px);line-height:1.2;letter-spacing:-0.02em;font-weight:500;color:var(--fg-primary);text-wrap:balance;">
                Six surfaces. Six teams. One source of truth underneath —
                <span style="color:var(--fg-tertiary);"> yours.</span>
            </h3>
        </div>
    </div>
</section>
