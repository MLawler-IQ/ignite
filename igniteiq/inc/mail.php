<?php
/**
 * Outbound mail: From identity + authenticated SMTP relay.
 *
 * WordPress' default wp_mail() sends as wordpress@<server-host> through the
 * local MTA. On WP Engine that host is *.wpenginepowered.com, which fails
 * SPF / DKIM / DMARC alignment for igniteiq.com — so contact-form
 * notifications to @igniteiq.com inboxes get spam-filtered or dropped.
 *
 * This routes wp_mail() through Google Workspace SMTP and stamps a real
 * @igniteiq.com From address. Because the recipients are on the same domain,
 * Workspace signs DKIM for igniteiq.com, alignment passes, and the mail lands
 * in the inbox.
 *
 * Credentials are NEVER committed. Define them in wp-config.php on EACH
 * environment (staging + production). See MAIL-SETUP.md at the repo root.
 *
 *   define('IIQ_SMTP_HOST',      'smtp-relay.gmail.com'); // or smtp.gmail.com
 *   define('IIQ_SMTP_PORT',      587);
 *   define('IIQ_SMTP_USER',      'notifications@igniteiq.com');
 *   define('IIQ_SMTP_PASS',      '...app password / relay credential...');
 *   define('IIQ_SMTP_SECURE',    'tls');                  // 'tls' (587) | 'ssl' (465)
 *   define('IIQ_MAIL_FROM',      'notifications@igniteiq.com');
 *   define('IIQ_MAIL_FROM_NAME', 'IgniteIQ Website');
 *
 * Everything here is inert until IIQ_SMTP_HOST is defined, so an unconfigured
 * environment keeps stock wp_mail() behavior. We deliberately do NOT stamp an
 * @igniteiq.com From address without an authenticated SMTP path, since an
 * unsigned From would *worsen* deliverability by failing DMARC.
 */
if (!defined('ABSPATH')) exit;

if (defined('IIQ_SMTP_HOST') && IIQ_SMTP_HOST) {

    add_filter('wp_mail_from', function ($from) {
        return defined('IIQ_MAIL_FROM') && IIQ_MAIL_FROM ? IIQ_MAIL_FROM : $from;
    });

    add_filter('wp_mail_from_name', function ($name) {
        return defined('IIQ_MAIL_FROM_NAME') && IIQ_MAIL_FROM_NAME ? IIQ_MAIL_FROM_NAME : $name;
    });

    add_action('phpmailer_init', function ($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host     = IIQ_SMTP_HOST;
        $phpmailer->Port     = defined('IIQ_SMTP_PORT') ? (int) IIQ_SMTP_PORT : 587;
        $phpmailer->SMTPAuth = defined('IIQ_SMTP_USER') && IIQ_SMTP_USER;

        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = IIQ_SMTP_USER;
            $phpmailer->Password = defined('IIQ_SMTP_PASS') ? IIQ_SMTP_PASS : '';
        }

        // PHPMailer maps 'tls' -> STARTTLS (587) and 'ssl' -> implicit TLS (465).
        $phpmailer->SMTPSecure = defined('IIQ_SMTP_SECURE') ? IIQ_SMTP_SECURE : 'tls';
    });
}
