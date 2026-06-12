<?php
if (!defined('ABSPATH')) exit;

/**
 * Site footer — defaults mirror SectionsB.jsx Footer component, editable
 * via Site Settings (ACF Options Page) once content is filled in.
 */

$default_columns = [
    [
        'heading' => 'Platform',
        'links' => [
            ['label' => 'How it works', 'url' => home_url('/how-it-works/')],
            ['label' => 'Ontology',     'url' => home_url('/ontology/')],
            ['label' => 'Sign In',      'url' => 'https://studio.igniteiq.com/#/signin', 'target' => '_blank'],
        ],
    ],
    [
        'heading' => 'Company',
        'links' => [
            ['label' => 'About',   'url' => home_url('/company/')],
            ['label' => 'Team',    'url' => home_url('/company/#team')],
            ['label' => 'Contact', 'url' => home_url('/company/#contact')],
        ],
    ],
    [
        'heading' => 'Resources',
        'links' => [
            ['label' => 'Security',         'url' => home_url('/how-it-works/#security')],
            ['label' => 'Marketing Portal', 'url' => 'https://portal.igniteiq.com', 'target' => '_blank'],
            ['label' => 'Privacy',          'url' => '#'],
            ['label' => 'Terms',            'url' => '#'],
        ],
    ],
];

$columns = function_exists('iiq_setting') ? iiq_setting('footer_columns', $default_columns) : $default_columns;
if (empty($columns) || !is_array($columns)) $columns = $default_columns;

$copyright = function_exists('iiq_setting')
    ? iiq_setting('footer_copyright_note', '© ' . date('Y') . ' IgniteIQ. Own your intelligence.')
    : '© ' . date('Y') . ' IgniteIQ. Own your intelligence.';
?>
<footer style="background:var(--ink-1000,#0b0b0c);color:var(--ink-200,#a0a0a0);padding:96px 32px 64px;border-top:1px solid var(--border-subtle);">
  <div style="max-width:1320px;margin:0 auto;display:grid;grid-template-columns:1.2fr repeat(3,1fr);gap:64px;" class="iiq-grid-split">

    <div>
      <a href="<?= esc_url(home_url('/')) ?>" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:#fff;">
        <img src="<?= esc_url(IIQ_URI . '/assets/img/logo-white.png') ?>" alt="IgniteIQ" style="object-fit:contain;width:28px;height:28px;">
        <span style="font-family:'Aeonik',sans-serif;font-weight:600;letter-spacing:-0.02em;font-size:24px;color:#fff;">IgniteIQ</span>
      </a>
      <p style="margin:24px 0 0;font-size:14px;color:var(--ink-300,#737373);max-width:280px;line-height:1.6;">Intelligence infrastructure for home services. Own your cloud. Own your data. Own your intelligence.</p>
    </div>

    <?php foreach ($columns as $col): ?>
      <?php $heading = is_array($col) && isset($col['heading']) ? $col['heading'] : ($col->heading ?? ''); ?>
      <?php $links = is_array($col) && isset($col['links']) ? $col['links'] : ($col->links ?? []); ?>
      <div>
        <h4 style="font-family:'Aeonik Fono',monospace;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ink-400,#525252);margin:0 0 16px;font-weight:500;"><?= esc_html($heading) ?></h4>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;">
          <?php foreach ((array) $links as $lnk): ?>
            <?php $label = is_array($lnk) ? ($lnk['label'] ?? '') : ($lnk->label ?? ''); ?>
            <?php $url = is_array($lnk) ? ($lnk['url'] ?? '#') : ($lnk->url ?? '#'); ?>
            <?php $target = is_array($lnk) ? ($lnk['target'] ?? '') : ($lnk->target ?? ''); ?>
            <li><a href="<?= esc_url($url) ?>"<?= $target ? ' target="' . esc_attr($target) . '" rel="noopener"' : '' ?> style="color:var(--ink-200,#a0a0a0);text-decoration:none;font-size:14px;"><?= esc_html($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="max-width:1320px;margin:64px auto 0;padding-top:32px;border-top:1px solid var(--ink-900,#1a1a1a);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <span style="font-family:'Aeonik Fono',monospace;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink-400,#525252);"><?= esc_html($copyright) ?></span>
    <span style="font-family:'Aeonik Fono',monospace;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink-400,#525252);">Framework v4.2</span>
  </div>
</footer>
