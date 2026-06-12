<?php
/**
 * IgniteIQ v2 theme bootstrap.
 */

if (!defined('ABSPATH')) exit;

define('IIQ_VERSION', '2.0.0');
define('IIQ_DIR', get_template_directory());
define('IIQ_URI', get_template_directory_uri());

require_once IIQ_DIR . '/inc/theme-setup.php';
require_once IIQ_DIR . '/inc/enqueue.php';
require_once IIQ_DIR . '/inc/nav-menus.php';
require_once IIQ_DIR . '/inc/acf-field-groups.php';
require_once IIQ_DIR . '/inc/acf-options-page.php';
require_once IIQ_DIR . '/inc/acf-render-flexible.php';
require_once IIQ_DIR . '/inc/contact-form.php';
require_once IIQ_DIR . '/inc/mail.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once IIQ_DIR . '/inc/cli.php';
}

/**
 * Admin notice if ACF Pro is not active.
 */
add_action('admin_notices', function () {
    if (!function_exists('acf_add_local_field_group')) {
        echo '<div class="notice notice-error"><p><strong>IgniteIQ v2:</strong> ACF Pro is required. Please install and activate Advanced Custom Fields Pro.</p></div>';
    }
});
