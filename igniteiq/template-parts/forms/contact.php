<?php
if (!defined('ABSPATH')) exit;

/**
 * Contact form — static markup, submitted via /assets/js/contact-form.js
 * to admin-ajax (action=iiq_contact). Spam mitigation: nonce, honeypot, time-trap.
 *
 * Strings are byte-accurate to the latest Claude Design export
 * (exports/latest/js/Contact.js). Office addresses, email, phone, topic
 * options, labels, placeholders, helper text, and button copy must match
 * the export exactly — including curly apostrophes (’), ellipses (…),
 * and the trailing arrow (→) on the submit button.
 *
 * If you change the topic options here, also update IIQ_CONTACT_TOPICS
 * in inc/contact-form.php — the handler validates against that list.
 */

$topics = [
    'I want a demo',
    'I’m evaluating IgniteIQ vs. another platform',
    'Partnership / integration',
    'Press / analyst',
    'Other',
];

$nonce = wp_create_nonce('iiq_contact');
?>
<section data-reveal class="iiq-pad iiq-section-pad" style="padding:120px 32px 160px;background:var(--bg-canvas);border-bottom:1px solid var(--border-default);">
  <div style="max-width:1320px;margin:0 auto;display:grid;grid-template-columns:0.9fr 1.4fr;gap:96px;align-items:flex-start;" class="iiq-grid-split">

    <aside>
      <?php // FIDELITY EXCEPTION: export's left column had Offices (San Diego HQ + SF) ?>
      <?php // and a Direct block (hello@igniteiq.com / phone). Replaced per ?>
      <?php // 2026-06 stakeholder ask: single HQ line, no public direct contact yet. ?>
      <p style="margin:0;font-family:var(--font-display);font-size:24px;font-weight:500;letter-spacing:-0.02em;line-height:1.4;color:var(--fg-primary);">Headquartered in San Diego, CA.</p>
    </aside>

    <div style="padding:40px 44px 44px;border:1px solid var(--border-default);background:var(--bg-base);">

      <div data-iiq-contact-success hidden style="padding:40px 0;">
        <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--ignite-500);">● Received</div>
        <h2 style="font-family:var(--font-display);font-size:36px;font-weight:600;letter-spacing:-0.03em;line-height:1.1;margin:20px 0 0;color:var(--fg-primary);">Thanks. We&rsquo;ll be in touch.</h2>
        <p style="margin-top:16px;font-size:16px;line-height:1.55;color:var(--fg-secondary);max-width:540px;">Someone on our team will reach out within one business day. In the meantime, feel free to see how it works, or read about the ontology.</p>
      </div>

      <form data-iiq-contact-form method="post" action="<?= esc_url(admin_url('admin-ajax.php')) ?>" novalidate style="display:flex;flex-direction:column;gap:24px;">
        <input type="hidden" name="action" value="iiq_contact">
        <input type="hidden" name="nonce" value="<?= esc_attr($nonce) ?>">
        <input type="hidden" name="rendered_at" value="0">

        <div class="iiq-honeypot" aria-hidden="true">
          <label>Website (leave blank)<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:var(--fg-tertiary);padding-bottom:16px;border-bottom:1px solid var(--border-default);margin-bottom:8px;">Send us a note</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <div>
            <label class="iiq-field-label" for="iiq-first">First name<span class="iiq-required"> *</span></label>
            <input class="iiq-input" id="iiq-first" name="first" type="text" required>
          </div>
          <div>
            <label class="iiq-field-label" for="iiq-last">Last name<span class="iiq-required"> *</span></label>
            <input class="iiq-input" id="iiq-last" name="last" type="text" required>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <div>
            <label class="iiq-field-label" for="iiq-email">Work email<span class="iiq-required"> *</span></label>
            <input class="iiq-input" id="iiq-email" name="email" type="email" required>
          </div>
          <div>
            <label class="iiq-field-label" for="iiq-company">Company<span class="iiq-required"> *</span></label>
            <input class="iiq-input" id="iiq-company" name="company" type="text" required>
          </div>
        </div>

        <div>
          <label class="iiq-field-label" for="iiq-role">Role</label>
          <input class="iiq-input" id="iiq-role" name="role" type="text" placeholder="e.g. CEO, Head of Operations">
        </div>

        <div>
          <label class="iiq-field-label" for="iiq-topic">What brings you here?</label>
          <select class="iiq-select" id="iiq-topic" name="topic">
            <option value="" disabled selected>Select one&hellip;</option>
            <?php foreach ($topics as $t): ?>
              <option value="<?= esc_attr($t) ?>"><?= esc_html($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="iiq-field-label" for="iiq-message">Tell us about your business</label>
          <textarea class="iiq-textarea" id="iiq-message" name="message" rows="4" placeholder="Number of locations, systems you run today, what you&rsquo;re trying to fix&hellip;"></textarea>
        </div>

        <div style="margin-top:8px;padding-top:24px;border-top:1px solid var(--border-default);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
          <span style="font-size:13px;color:var(--fg-tertiary);">We respond within one business day.</span>
          <button type="submit" style="appearance:none;border:none;cursor:pointer;background:var(--ignite-500);color:var(--ink-50);font-family:var(--font-display);font-weight:500;font-size:16px;letter-spacing:-0.01em;text-transform:none;padding:14px 28px;border-radius:4px;display:inline-flex;align-items:center;gap:8px;">Send message <span style="font-size:14px;">&rarr;</span></button>
        </div>
      </form>
    </div>

  </div>
</section>
