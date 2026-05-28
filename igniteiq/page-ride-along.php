<?php
/**
 * Template Name: Ride along
 * Slug: ride-along
 *
 * Ride-along page renders the self-contained `ride-along-page` section
 * partial directly, bypassing the ACF flexible-content pipeline used by
 * the other cornerstone pages. The page is a narrative scroll (Demo.jsx
 * port) and has no per-block authoring surface in WP admin — content
 * lives in the partial.
 */
if (!defined('ABSPATH')) exit;
get_header();
if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('template-parts/sections/ride-along-page');
    }
}
get_footer();
