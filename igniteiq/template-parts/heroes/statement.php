<?php
if (!defined('ABSPATH')) exit;
$eyebrow        = get_sub_field('eyebrow') ?: '';
$headline_lines = get_sub_field('headline_lines') ?: [];
// FIDELITY: ACF schema for hero_statement uses `subhead` (acf-field-groups.php:170).
// Fall back to `body` for back-compat with any other consumer.
$body           = get_sub_field('subhead') ?: (get_sub_field('body') ?: '');
$primary_cta    = get_sub_field('primary_cta') ?: ['label' => '', 'url' => ''];
$secondary_cta  = get_sub_field('secondary_cta') ?: ['label' => '', 'url' => ''];
$stats          = get_sub_field('stats') ?: [];

// Wave V-4 — tagline preset overrides headline_lines when set.
$tagline_choice = get_sub_field('tagline_choice') ?: '__custom__';
if ($tagline_choice && $tagline_choice !== '__custom__') {
    $headline_lines = [['line' => $tagline_choice]];
}

// Wave V-4 — Statement / Team variant. Team replaces the big tagline with
// the export's "Built by the operators who ran the trucks." headline.
$variant = get_sub_field('variant') ?: 'statement';
if ($variant === 'team') {
    $headline_lines = [['line' => 'Built by the operators who ran the trucks.']];
}

// Statement hero typically has a single declarative statement.
// Concatenate headline_lines into a single statement for h1 rendering.
$statement = '';
if (!empty($headline_lines)) {
    $parts = [];
    foreach ($headline_lines as $row) {
        $parts[] = is_array($row) ? ($row['line'] ?? '') : $row;
    }
    $statement = implode(' ', array_filter($parts));
} else {
    $statement = 'Owning your intelligence is the only advantage that compounds.';
}

if (empty($stats)) {
    $stats = [
        ['label' => 'Time to deployed',   'value' => '< 7 days'],
        ['label' => 'Systems unified',    'value' => '25+'],
        ['label' => 'Customer-owned',     'value' => '100%'],
        ['label' => 'Framework version',  'value' => 'v4.2'],
    ];
}
?>
<section style="min-height: 100vh; background: var(--bg-canvas, #fff); color: var(--fg-primary); border-bottom: 1px solid var(--border-subtle); display: flex; flex-direction: column; padding-top: 96px; position: relative; overflow: hidden;">
  <div aria-hidden data-iiq-parallax-coefficient="0.35" style="position: absolute; inset: -20% 0 -20% 0; z-index: 0; background-image: linear-gradient(var(--border-subtle) 1px, transparent 1px), linear-gradient(90deg, var(--border-subtle) 1px, transparent 1px); background-size: 72px 72px; opacity: 0.32; will-change: transform; pointer-events: none; mask-image: radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%); -webkit-mask-image: radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%);"></div>
  <div aria-hidden data-iiq-parallax-coefficient="0.25" style="position: absolute; bottom: 28px; left: 28px; width: 14px; height: 14px; border-bottom: 1px solid var(--border-default); border-left: 1px solid var(--border-default); will-change: transform;"></div>
  <div aria-hidden data-iiq-parallax-coefficient="0.25" style="position: absolute; bottom: 28px; right: 28px; width: 14px; height: 14px; border-bottom: 1px solid var(--border-default); border-right: 1px solid var(--border-default); will-change: transform;"></div>

  <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 64px 48px; position: relative; z-index: 1;">
    <div style="max-width: 1320px; width: 100%; text-align: left;">
      <?php if ($eyebrow): ?>
        <span class="iiq-eyebrow" style="font-family: 'Aeonik Fono', monospace; font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--ignite-500); display: inline-flex; align-items: center; gap: 8px;">
          <span style="width:6px;height:6px;border-radius:50%;background:var(--ignite-500);animation: iiqPulse 2s ease-in-out infinite;"></span>
          <?= esc_html($eyebrow) ?>
        </span>
      <?php endif; ?>
      <h1 style="margin: 32px 0 0; font-family: var(--font-display); font-weight: 600; letter-spacing: -0.045em; line-height: 0.95; color: var(--fg-primary); text-wrap: balance; font-size: clamp(40px, 10vw, 140px); overflow-wrap: break-word;">
        <?= esc_html($statement) ?>
      </h1>
      <?php if ($body): ?>
        <p style="margin: 40px 0 0; max-width: 820px; line-height: 1.5; color: var(--fg-secondary); text-wrap: pretty; font-size: clamp(16px, 2.6vw, 22px);">
          <?= wp_kses_post($body) ?>
        </p>
      <?php endif; ?>
      <?php if (!empty($primary_cta['url']) || !empty($secondary_cta['url'])): ?>
        <div style="margin-top: 40px; display: flex; gap: 12px;">
          <?php if (!empty($primary_cta['url'])): ?>
            <a href="<?= esc_url($primary_cta['url']) ?>" class="iiq-btn" style="font-size: 14px; font-weight: 500; padding: 14px 24px; background: var(--ignite-500); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; border-radius: 6px;">
              <?= esc_html($primary_cta['label']) ?> <span>&rarr;</span>
            </a>
          <?php endif; ?>
          <?php if (!empty($secondary_cta['url'])): ?>
            <a href="<?= esc_url($secondary_cta['url']) ?>" class="iiq-btn-ghost" style="font-size: 14px; font-weight: 500; padding: 14px 24px; background: transparent; color: var(--fg-primary); text-decoration: none; border: 1px solid var(--border-default); border-radius: 6px;">
              <?= esc_html($secondary_cta['label']) ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($stats)): ?>
        <div class="iiq-hero-stats" data-iiq-parallax-coefficient="0.12" style="margin-top: 88px; padding-top: 28px; border-top: 1px solid var(--border-default); display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; text-align: left; max-width: 1200px; margin-left: auto; margin-right: auto; will-change: transform;">
          <?php foreach ($stats as $stat):
            $label = is_array($stat) ? ($stat['label'] ?? '') : '';
            $value = is_array($stat) ? ($stat['value'] ?? '') : '';
          ?>
            <div>
              <div style="font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--fg-tertiary);"><?= esc_html($label) ?></div>
              <div style="margin-top: 10px; font-family: var(--font-display); font-size: clamp(28px, 3.2vw, 40px); font-weight: 600; letter-spacing: -0.02em; color: var(--fg-primary);"><?= esc_html($value) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
