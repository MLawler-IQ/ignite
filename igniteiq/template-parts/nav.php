<?php
if (!defined('ABSPATH')) exit;

/**
 * IgniteIQ primary navigation.
 *
 * Ports Nav.jsx — fixed top bar, scroll-aware backdrop blur, desktop link
 * row, mobile burger that opens a side drawer (state machine in nav.js).
 *
 * `inverse` query var (set by hero template-parts when needed) flips colors
 * to white-on-dark for cinematic hero pages.
 */

$inverse = (bool) get_query_var('iiq_nav_inverse', false);

// Sign-in page renders a full-viewport split with a dark <aside> at the
// top-left. The default black logo/wordmark would be invisible against it,
// so force inverse colors here. (header.php sets iiq_nav_inverse from the
// hero_cinematic ACF row, which signin doesn't use — we don't touch it.)
if (!$inverse) {
    $iiq_is_signin = (function_exists('is_page_template') && is_page_template('page-signin.php'))
        || (function_exists('is_page') && is_page('signin'));
    if ($iiq_is_signin) {
        $inverse = true;
    }
}

$logo_src   = $inverse ? IIQ_URI . '/assets/img/logo-white.png' : IIQ_URI . '/assets/img/logo-black.png';
$fg         = $inverse ? 'var(--ink-50)' : 'var(--fg-primary)';
$fg_muted   = $inverse ? 'var(--ink-50)' : 'var(--fg-secondary)';

// Items — pulled from primary menu when assigned, else hardcoded fallback.
$items = [];
$menu_locations = get_nav_menu_locations();
if (!empty($menu_locations['primary'])) {
    $menu = wp_get_nav_menu_items($menu_locations['primary']);
    if ($menu) {
        foreach ($menu as $item) {
            $items[] = ['label' => $item->title, 'href' => $item->url];
        }
    }
}
if (empty($items)) {
    $items = [
        ['label' => 'How it works', 'href' => home_url('/how-it-works/')],
        ['label' => 'Ontology',     'href' => home_url('/ontology/')],
        ['label' => 'Company',      'href' => home_url('/company/')],
    ];
}

// Auto-append "Ride along" between Ontology and Company when the page
// is published, regardless of whether the source was the WP primary menu
// or the hardcoded fallback. The page is seeded as draft by cli.php
// (Wave V-3) so the nav stays clean until Matt publishes from WP admin.
// If admin has already added Ride along to the primary menu by hand we
// skip the auto-append to avoid a duplicate.
$ride_along = get_page_by_path('ride-along', OBJECT, 'page');
if ($ride_along && $ride_along->post_status === 'publish') {
    $ride_along_url = home_url('/ride-along/');
    $already_present = false;
    foreach ($items as $it) {
        if (rtrim($it['href'], '/') === rtrim($ride_along_url, '/')
            || stripos($it['label'], 'Ride along') === 0) {
            $already_present = true;
            break;
        }
    }
    if (!$already_present) {
        $insert_idx = count($items);
        foreach ($items as $i => $it) {
            if (stripos($it['label'], 'Company') === 0) {
                $insert_idx = $i;
                break;
            }
        }
        array_splice($items, $insert_idx, 0, [[
            'label' => 'Ride along',
            'href'  => $ride_along_url,
        ]]);
    }
}

// Sign-in page renders a minimal nav — drop the link row entirely so
// the auth surface stays uncluttered (visual-iiq-diff SI1, V-3 lock).
$iiq_is_signin_for_nav = (function_exists('is_page_template') && is_page_template('page-signin.php'))
    || (function_exists('is_page') && is_page('signin'));
if ($iiq_is_signin_for_nav) {
    $items = [];
}
// FIDELITY EXCEPTION: nav "Sign in" link points to the external Studio app
// (separate Vue/React product at studio.igniteiq.com) instead of the local
// /signin/ WP page. The /signin/ page is still reachable by direct URL.
$signin_url  = 'https://studio.igniteiq.com/#/signin';
$contact_url = home_url('/contact/');
?>
<nav data-iiq-nav data-inverse="<?= $inverse ? '1' : '0' ?>" style="position:fixed;top:0;left:0;right:0;z-index:100;background:transparent;border-bottom:1px solid transparent;transition:background 240ms var(--ease-standard,ease), border-color 240ms var(--ease-standard,ease);">
  <div style="max-width:1440px;margin:0 auto;display:flex;align-items:center;padding:14px 24px;gap:36px;">

    <a href="<?= esc_url(home_url('/')) ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
      <img src="<?= esc_url($logo_src) ?>" alt="" style="object-fit:contain;width:30px;height:30px;">
      <span style="font-family:var(--font-display, 'Aeonik', sans-serif);font-weight:600;letter-spacing:-0.02em;color:<?= esc_attr($fg) ?>;font-size:28px;">IgniteIQ</span>
    </a>

    <ul class="iiq-nav-desktop" style="display:flex;gap:30px;list-style:none;margin:0;padding:0;margin-left:18px;">
      <?php foreach ($items as $it): ?>
        <li style="font-size:16px;">
          <a href="<?= esc_url($it['href']) ?>" style="font-weight:500;color:<?= esc_attr($fg_muted) ?>;text-decoration:none;letter-spacing:-0.005em;transition:color 140ms var(--ease-standard,ease);font-size:16px;"><?= esc_html($it['label']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!$iiq_is_signin_for_nav): ?>
    <div class="iiq-nav-desktop" style="margin-left:auto;display:flex;gap:20px;align-items:center;">
      <a href="<?= esc_url($signin_url) ?>" target="_blank" rel="noopener" style="font-weight:500;color:<?= esc_attr($fg_muted) ?>;text-decoration:none;font-size:16px;">Sign in</a>
      <a href="<?= esc_url($contact_url) ?>" style="font-weight:500;color:<?= esc_attr($fg_muted) ?>;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:16px;">Contact <span style="font-size:13px;">→</span></a>
    </div>
    <?php endif; ?>

    <button type="button" class="iiq-nav-burger" data-iiq-nav-burger aria-label="Open menu" aria-expanded="false" aria-controls="iiq-nav-drawer" style="margin-left:auto;appearance:none;background:transparent;border:none;cursor:pointer;padding:8px;color:<?= esc_attr($fg) ?>;align-items:center;justify-content:center;">
      <span aria-hidden="true" style="position:relative;width:22px;height:14px;display:inline-block;">
        <span style="position:absolute;left:0;top:2px;height:1.5px;width:22px;background:currentColor;transition:transform 220ms ease, top 220ms ease, opacity 180ms ease;"></span>
        <span style="position:absolute;left:0;top:6.5px;height:1.5px;width:22px;background:currentColor;transition:opacity 180ms ease;"></span>
        <span style="position:absolute;left:0;top:11px;height:1.5px;width:22px;background:currentColor;transition:transform 220ms ease, top 220ms ease;"></span>
      </span>
    </button>
  </div>
</nav>

<div class="iiq-nav-scrim" data-iiq-nav-scrim aria-hidden="true"></div>

<aside id="iiq-nav-drawer" class="iiq-nav-drawer" data-iiq-nav-drawer aria-hidden="true" aria-label="Mobile navigation">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;">
    <span style="font-family:var(--font-mono, 'Aeonik Fono', monospace);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--fg-tertiary);">Menu</span>
    <button type="button" data-iiq-nav-close aria-label="Close menu" style="appearance:none;background:transparent;border:none;cursor:pointer;padding:8px;color:var(--fg-primary);">
      <span aria-hidden="true" style="position:relative;width:22px;height:14px;display:inline-block;">
        <span style="position:absolute;left:0;top:6.5px;height:1.5px;width:22px;background:currentColor;transform:rotate(45deg);"></span>
        <span style="position:absolute;left:0;top:6.5px;height:1.5px;width:22px;background:currentColor;transform:rotate(-45deg);"></span>
      </span>
    </button>
  </div>

  <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;">
    <?php foreach ($items as $it): ?>
      <li style="border-top:1px solid var(--border-subtle);">
        <a href="<?= esc_url($it['href']) ?>" style="display:block;padding:20px 0;font-family:var(--font-display, 'Aeonik', sans-serif);font-size:24px;font-weight:600;letter-spacing:-0.02em;color:var(--fg-primary);text-decoration:none;"><?= esc_html($it['label']) ?></a>
      </li>
    <?php endforeach; ?>
    <li style="border-top:1px solid var(--border-subtle);border-bottom:1px solid var(--border-subtle);">
      <a href="<?= esc_url($signin_url) ?>" target="_blank" rel="noopener" style="display:block;padding:20px 0;font-family:var(--font-display, 'Aeonik', sans-serif);font-size:24px;font-weight:600;letter-spacing:-0.02em;color:var(--fg-primary);text-decoration:none;">Sign in</a>
    </li>
  </ul>

  <a href="<?= esc_url($contact_url) ?>" style="margin-top:32px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:16px 24px;background:var(--ignite-500);color:var(--ink-50);font-family:var(--font-display, 'Aeonik', sans-serif);font-weight:500;font-size:16px;letter-spacing:-0.01em;text-decoration:none;border-radius:4px;">Contact <span style="font-size:14px;">→</span></a>
</aside>
