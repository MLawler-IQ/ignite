<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('get_sub_field')) return;
require_once __DIR__ . '/_helpers.php';

/**
 * Team section — ports Company.jsx TeamDeep (export 20260604-website-2-6).
 *
 * Header: two-column when a `lede` is set — stacked headline left
 * (headline_lead carries the upper lines with <br>, headline_gap the muted
 * final line), lede paragraph right. Falls back to the legacy single
 * headline when no lede.
 *
 * Members: when `featured_count` > 0 the first N members render in a 3-up
 * row of large cards (the founders), and the remainder render in a centered
 * 2-up row. featured_count = 0 → one uniform 3-col grid (legacy behaviour).
 *
 * Card: 4:5 portrait photo (neutral placeholder with initials until a
 * headshot is uploaded via the ACF Photo field), name, role (mono eyebrow),
 * credential (bold display lead), bio.
 */

$headline      = get_sub_field('headline') ?: '';
$headline_lead = get_sub_field('headline_lead') ?: '';
$headline_gap  = get_sub_field('headline_gap') ?: '';
$lede          = get_sub_field('lede') ?: '';
$featured      = (int) (get_sub_field('featured_count') ?: 0);
$members       = get_sub_field('members') ?: [];
$variant       = iiq_section_variant();

if (!function_exists('iiq_team_initials')) {
    function iiq_team_initials($name) {
        $name = trim((string) $name);
        if (!$name) return '';
        $parts = preg_split('/\s+/', $name);
        $i = '';
        foreach ($parts as $p) {
            if ($p !== '') $i .= mb_substr($p, 0, 1);
            if (mb_strlen($i) >= 2) break;
        }
        return strtoupper($i);
    }
}

/**
 * Render one member card. $large only affects nothing structurally today
 * (both rows share the card), but is kept so future tier-specific styling
 * has a hook.
 */
if (!function_exists('iiq_team_card')) {
    function iiq_team_card($m) {
        $name       = isset($m['name']) ? $m['name'] : '';
        $role       = isset($m['role']) ? $m['role'] : '';
        $credential = isset($m['credential']) ? $m['credential'] : '';
        $bio        = isset($m['bio']) ? $m['bio'] : '';
        $photo      = isset($m['photo']) ? $m['photo'] : null;
        $has_photo  = is_array($photo) && !empty($photo['ID']);

        // Bundled theme headshot fallback: assets/img/team/<slug>.jpg.
        // Survives `wp igniteiq seed --force` (versioned in the theme) and
        // deploys to every environment via rsync — no media-library upload.
        $slug      = isset($m['photo_slug']) ? sanitize_file_name($m['photo_slug']) : '';
        $theme_src = '';
        if (!$has_photo && $slug && defined('IIQ_DIR') && defined('IIQ_URI')) {
            if (file_exists(IIQ_DIR . '/assets/img/team/' . $slug . '.jpg')) {
                $theme_src = IIQ_URI . '/assets/img/team/' . $slug . '.jpg';
            }
        }
        ob_start(); ?>
        <article style="border:1px solid var(--border-default,#C9C5BD);background:var(--bg-canvas,#fff);display:flex;flex-direction:column;height:100%;">
            <div style="padding:16px 16px 0;">
                <?php if ($has_photo): ?>
                    <div style="width:100%;aspect-ratio:4 / 5;overflow:hidden;background:var(--bg-sunken,#F4EFE4);display:block;">
                        <?= wp_get_attachment_image(
                            (int) $photo['ID'],
                            'medium_large',
                            false,
                            ['style' => 'width:100%;height:100%;object-fit:cover;display:block;', 'alt' => esc_attr($name)]
                        ) ?>
                    </div>
                <?php elseif ($theme_src): ?>
                    <div style="width:100%;aspect-ratio:4 / 5;overflow:hidden;background:var(--bg-sunken,#F4EFE4);display:block;">
                        <img src="<?= esc_url($theme_src) ?>" alt="<?= esc_attr($name) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;object-position:center top;display:block;">
                    </div>
                <?php else: ?>
                    <div aria-hidden="true" style="width:100%;aspect-ratio:4 / 5;background:var(--bg-sunken,#F4EFE4);display:flex;align-items:center;justify-content:center;">
                        <span style="font-family:var(--font-display);font-size:40px;font-weight:600;letter-spacing:-0.02em;color:var(--fg-tertiary,#9A958C);"><?= esc_html(iiq_team_initials($name)) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding:24px 28px 30px;display:flex;flex-direction:column;gap:16px;flex:1;">
                <div>
                    <?php if ($name): ?>
                        <h3 style="font-family:var(--font-display);font-size:26px;font-weight:600;letter-spacing:-0.03em;margin:0;color:var(--fg-primary);line-height:1.1;"><?= esc_html($name) ?></h3>
                    <?php endif; ?>
                    <?php if ($role): ?>
                        <div style="margin-top:7px;font-family:var(--font-mono);font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:var(--ignite-500);"><?= esc_html($role) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($credential): ?>
                    <p style="margin:0;font-family:var(--font-display);font-size:19px;font-weight:600;letter-spacing:-0.02em;line-height:1.3;color:var(--fg-primary);text-wrap:balance;"><?= esc_html($credential) ?></p>
                <?php endif; ?>
                <?php if ($bio): ?>
                    <p style="font-size:15px;line-height:1.6;color:var(--fg-secondary,#5A5A60);margin:0;"><?= wp_kses_post($bio) ?></p>
                <?php endif; ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }
}

// Split members into featured (row 1) + remainder (row 2).
$row1 = ($featured > 0) ? array_slice($members, 0, $featured) : $members;
$row2 = ($featured > 0) ? array_slice($members, $featured) : [];
?>
<section data-reveal class="iiq-pad iiq-section-pad"<?= iiq_section_variant_style($variant) ?>>
    <div style="position:relative;max-width:1320px;margin:0 auto;">
        <?php iiq_section_marker(); ?>

        <?php if ($lede): ?>
            <div class="iiq-team-header" style="margin-top:8px;display:grid;grid-template-columns:1fr 1.25fr;gap:80px;align-items:flex-start;">
                <h2 class="iiq-display-lg" style="margin:0;font-family:var(--font-display);font-size:clamp(44px,5.6vw,84px);font-weight:600;letter-spacing:-0.04em;line-height:0.98;color:var(--fg-primary);">
                    <?= wp_kses_post($headline_lead) ?><?php if ($headline_gap): ?><br><span style="color:var(--fg-tertiary);"><?= wp_kses_post($headline_gap) ?></span><?php endif; ?>
                </h2>
                <p style="font-size:21px;line-height:1.55;color:var(--fg-primary);margin:0;font-weight:500;text-wrap:pretty;">
                    <?= wp_kses_post($lede) ?>
                </p>
            </div>
        <?php elseif ($headline_lead && $headline_gap): ?>
            <h2 class="iiq-display-lg" style="margin:0 0 56px;max-width:1100px;font-family:var(--font-display);font-size:clamp(44px,5.6vw,88px);font-weight:600;letter-spacing:-0.04em;line-height:0.98;">
                <?= wp_kses_post($headline_lead) ?><span style="color:var(--fg-tertiary);"> <?= wp_kses_post($headline_gap) ?></span>
            </h2>
        <?php elseif ($headline): ?>
            <h2 class="iiq-display-lg" style="margin:0 0 56px;max-width:880px;font-family:var(--font-display);font-weight:600;letter-spacing:-0.025em;line-height:1.05;">
                <?= wp_kses_post($headline) ?>
            </h2>
        <?php endif; ?>

        <?php if (!empty($row1) && is_array($row1)): ?>
            <div class="iiq-team-row1 iiq-grid-3" style="margin-top:80px;display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
                <?php foreach ($row1 as $m) echo iiq_team_card($m); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($row2) && is_array($row2)): ?>
            <div class="iiq-team-row2 iiq-grid-2" style="margin-top:24px;max-width:872px;margin-left:auto;margin-right:auto;display:grid;grid-template-columns:repeat(2,1fr);gap:24px;">
                <?php foreach ($row2 as $m) echo iiq_team_card($m); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
