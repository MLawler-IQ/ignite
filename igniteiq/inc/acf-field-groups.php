<?php
/**
 * ACF Pro Field Group Registration
 *
 * Registers three field groups:
 *   A. Page Sections (Flexible Content)
 *   B. Page Meta (SEO + schema)
 *   C. Site Settings (Options page)
 *
 * Silently no-ops if ACF Pro is not active.
 *
 * @package IgniteIQ
 */

if (!defined('ABSPATH')) { exit; }

if (function_exists('acf_add_local_field_group')) {

    // ──────────────────────────────────────────────────────────────────
    // Shared bits
    // ──────────────────────────────────────────────────────────────────
    $iiq_hide_on_screen = ['custom_fields', 'discussion', 'comments'];

    // Reusable: settings sub-group attached to most layouts.
    //
    // FIDELITY/ACF FIX (Wave 8): each layout now gets its OWN settings group
    // with a unique field key, instead of all 14 layouts sharing the single
    // 'field_iiq_layout_settings' key. ACF stores nested-group meta keyed by
    // field key — when 14 layouts shared the same key, ACF refused to persist
    // any of them, and `get_sub_field('settings')` returned null everywhere
    // (so theme_variant + section_index + section_label never made it to
    // postmeta — verified via `wp post meta list 13` on staging, which showed
    // zero `*_settings_*` keys for the ontology page).
    //
    // Factory mirrors the existing $iiq_cta_group / $iiq_headline_lines
    // pattern. Each sub-field also gets a unique key suffix so the inner
    // schema is fully namespaced.
    $iiq_settings_group_for = function ($key_suffix) {
        return [
            'key'        => 'field_iiq_settings_' . $key_suffix,
            'label'      => 'Section settings',
            'name'       => 'settings',
            'type'       => 'group',
            'layout'     => 'block',
            'sub_fields' => [
                [
                    'key'   => 'field_iiq_settings_' . $key_suffix . '_section_index',
                    'label' => 'Section index',
                    'name'  => 'section_index',
                    'type'  => 'text',
                    'instructions' => 'Two-digit prefix shown above the section, e.g. "01".',
                ],
                [
                    'key'   => 'field_iiq_settings_' . $key_suffix . '_section_label',
                    'label' => 'Section label',
                    'name'  => 'section_label',
                    'type'  => 'text',
                ],
                [
                    'key'     => 'field_iiq_settings_' . $key_suffix . '_theme_variant',
                    'label'   => 'Theme variant',
                    'name'    => 'theme_variant',
                    'type'    => 'select',
                    'choices' => [
                        'light'  => 'Light',
                        'dark'   => 'Dark',
                        'sunken' => 'Sunken',
                        'canvas' => 'Canvas',
                    ],
                    'default_value' => 'light',
                    'return_format' => 'value',
                ],
            ],
        ];
    };

    // Reusable CTA group factory
    $iiq_cta_group = function ($key_suffix, $label, $name) {
        return [
            'key'        => 'field_iiq_cta_' . $key_suffix,
            'label'      => $label,
            'name'       => $name,
            'type'       => 'group',
            'layout'     => 'block',
            'sub_fields' => [
                [
                    'key'   => 'field_iiq_cta_label_' . $key_suffix,
                    'label' => 'Label',
                    'name'  => 'label',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_iiq_cta_url_' . $key_suffix,
                    'label' => 'URL',
                    'name'  => 'url',
                    'type'  => 'text',
                ],
            ],
        ];
    };

    // Reusable headline_lines repeater
    $iiq_headline_lines = function ($key_suffix) {
        return [
            'key'          => 'field_iiq_headline_lines_' . $key_suffix,
            'label'        => 'Headline lines',
            'name'         => 'headline_lines',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add line',
            'sub_fields'   => [
                [
                    'key'   => 'field_iiq_headline_line_' . $key_suffix,
                    'label' => 'Line',
                    'name'  => 'line',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_iiq_headline_line_muted_' . $key_suffix,
                    'label' => 'Muted',
                    'name'  => 'muted',
                    'type'  => 'true_false',
                    'ui'    => 1,
                    'default_value' => 0,
                ],
            ],
        ];
    };

    // Reusable stats repeater
    $iiq_stats_repeater = function ($key_suffix, $with_footnote = false) {
        $sub = [
            [
                'key' => 'field_iiq_stat_value_' . $key_suffix,
                'label' => 'Value', 'name' => 'value', 'type' => 'text',
            ],
            [
                'key' => 'field_iiq_stat_label_' . $key_suffix,
                'label' => 'Label', 'name' => 'label', 'type' => 'text',
            ],
        ];
        if ($with_footnote) {
            $sub[] = [
                'key' => 'field_iiq_stat_footnote_' . $key_suffix,
                'label' => 'Footnote', 'name' => 'footnote', 'type' => 'text',
            ];
        }
        return [
            'key'          => 'field_iiq_stats_' . $key_suffix,
            'label'        => 'Stats',
            'name'         => 'stats',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add stat',
            'sub_fields'   => $sub,
        ];
    };

    $iiq_diagram_choices = [
        'stack'          => 'Stack diagram',
        'platform-stack' => 'Platform stack',
        'arch-ontology'  => 'Architecture · ontology',
        'operator-stack' => 'Operator stack (problem)',
        'cloud-arch'     => 'Cloud architecture',
        'framework'      => 'Framework',
        'boundary'       => 'Sovereign boundary',
    ];

    // ──────────────────────────────────────────────────────────────────
    // GROUP A — PAGE SECTIONS (Flexible Content)
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'    => 'group_iiq_page_sections',
        'title'  => 'Page Sections',
        'fields' => [
            [
                'key'          => 'field_iiq_page_sections',
                'label'        => 'Page sections',
                'name'         => 'page_sections',
                'type'         => 'flexible_content',
                'button_label' => 'Add section',
                'layouts'      => [

                    // ─── hero_statement ───
                    'layout_iiq_hero_statement' => [
                        'key'        => 'layout_iiq_hero_statement',
                        'name'       => 'hero_statement',
                        'label'      => 'Hero · Statement',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_hs_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            $iiq_headline_lines('hs'),
                            // Tagline toggle (Wave V-4). The export ships four
                            // candidate taglines in its dev tweaks panel. If
                            // `tagline_choice` is set to anything other than
                            // '__custom__', heroes/statement.php overrides
                            // headline_lines with the chosen string. Authors who
                            // want full freedom pick '__custom__' (the default)
                            // and edit headline_lines directly.
                            [
                                'key'           => 'field_iiq_hs_tagline_choice',
                                'label'         => 'Tagline (preset)',
                                'name'          => 'tagline_choice',
                                'type'          => 'select',
                                'instructions'  => 'Override headline_lines with one of the four export-canonical taglines. Use "Custom" to author headline_lines manually.',
                                'choices'       => [
                                    '__custom__'                                           => 'Custom (use headline_lines above)',
                                    'The Decision Engine for Modern Trades.'               => 'The Decision Engine for Modern Trades.',
                                    'The Intelligence Decision Engine for Modern Trades.'  => 'The Intelligence Decision Engine for Modern Trades.',
                                    'The Intelligence Engine for Modern Trades.'           => 'The Intelligence Engine for Modern Trades.',
                                    'Intelligence Infrastructure for Modern Trades.'       => 'Intelligence Infrastructure for Modern Trades.',
                                ],
                                'default_value' => '__custom__',
                                'allow_null'    => 0,
                                'return_format' => 'value',
                            ],
                            // Variant toggle (Wave V-4). Statement = the big
                            // single-line tagline hero (current default).
                            // Team = the "Built by the operators who ran the
                            // trucks." treatment (the export's dev tweaks
                            // expose this as the alternative shipped variant).
                            [
                                'key'           => 'field_iiq_hs_variant',
                                'label'         => 'Variant',
                                'name'          => 'variant',
                                'type'          => 'select',
                                'instructions'  => 'Statement = single-line tagline (default). Team = "Built by the operators…" treatment.',
                                'choices'       => [
                                    'statement' => 'Statement',
                                    'team'      => 'Team',
                                ],
                                'default_value' => 'statement',
                                'allow_null'    => 0,
                                'return_format' => 'value',
                            ],
                            ['key' => 'field_iiq_hs_subhead', 'label' => 'Subhead', 'name' => 'subhead', 'type' => 'textarea', 'rows' => 3],
                            $iiq_cta_group('hs_primary', 'Primary CTA', 'primary_cta'),
                            $iiq_cta_group('hs_secondary', 'Secondary CTA', 'secondary_cta'),
                            $iiq_stats_repeater('hs'),
                        ],
                    ],

                    // ─── hero_editorial ───
                    'layout_iiq_hero_editorial' => [
                        'key'        => 'layout_iiq_hero_editorial',
                        'name'       => 'hero_editorial',
                        'label'      => 'Hero · Editorial',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_he_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            $iiq_headline_lines('he'),
                            ['key' => 'field_iiq_he_body', 'label' => 'Body', 'name' => 'body', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            $iiq_cta_group('he_primary', 'Primary CTA', 'primary_cta'),
                            $iiq_cta_group('he_secondary', 'Secondary CTA', 'secondary_cta'),
                            ['key' => 'field_iiq_he_dark', 'label' => 'Dark', 'name' => 'dark', 'type' => 'true_false', 'ui' => 1],
                            ['key' => 'field_iiq_he_size_variant', 'label' => 'Size variant', 'name' => 'size_variant', 'type' => 'select', 'choices' => ['' => 'Default', 'compact' => 'Compact (ArchHero)', 'inline-2tone' => 'Inline 2-tone (CompanyHero)'], 'default_value' => '', 'allow_null' => 1, 'return_format' => 'value'],
                            ['key' => 'field_iiq_he_headline_inline', 'label' => 'Headline inline', 'name' => 'headline_inline', 'type' => 'true_false', 'instructions' => 'Render headline_lines inline (no separating space). Caller controls whitespace via leading/trailing spaces inside each line.'],
                            $iiq_stats_repeater('he'),
                        ],
                    ],

                    // ─── hero_cinematic ───
                    'layout_iiq_hero_cinematic' => [
                        'key'        => 'layout_iiq_hero_cinematic',
                        'name'       => 'hero_cinematic',
                        'label'      => 'Hero · Cinematic',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_hc_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            $iiq_headline_lines('hc'),
                            ['key' => 'field_iiq_hc_body', 'label' => 'Body', 'name' => 'body', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            $iiq_cta_group('hc_primary', 'Primary CTA', 'primary_cta'),
                            $iiq_cta_group('hc_secondary', 'Secondary CTA', 'secondary_cta'),
                            $iiq_stats_repeater('hc'),
                        ],
                    ],

                    // ─── hero_split ───
                    'layout_iiq_hero_split' => [
                        'key'        => 'layout_iiq_hero_split',
                        'name'       => 'hero_split',
                        'label'      => 'Hero · Split',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_hsp_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            $iiq_headline_lines('hsp'),
                            ['key' => 'field_iiq_hsp_body', 'label' => 'Body', 'name' => 'body', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            $iiq_cta_group('hsp_primary', 'Primary CTA', 'primary_cta'),
                            $iiq_cta_group('hsp_secondary', 'Secondary CTA', 'secondary_cta'),
                            ['key' => 'field_iiq_hsp_image', 'label' => 'Media image', 'name' => 'media_image', 'type' => 'image', 'return_format' => 'array'],
                            [
                                'key' => 'field_iiq_hsp_position',
                                'label' => 'Media position',
                                'name' => 'media_position',
                                'type' => 'select',
                                'choices' => ['left' => 'Left', 'right' => 'Right'],
                                'default_value' => 'right',
                                'return_format' => 'value',
                            ],
                        ],
                    ],

                    // ─── hero_minimal ───
                    'layout_iiq_hero_minimal' => [
                        'key'        => 'layout_iiq_hero_minimal',
                        'name'       => 'hero_minimal',
                        'label'      => 'Hero · Minimal',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_hm_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            $iiq_headline_lines('hm'),
                            ['key' => 'field_iiq_hm_body', 'label' => 'Body', 'name' => 'body', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                        ],
                    ],

                    // ─── hero_ontology ───
                    'layout_iiq_hero_ontology' => [
                        'key'        => 'layout_iiq_hero_ontology',
                        'name'       => 'hero_ontology',
                        'label'      => 'Hero — Ontology',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_ho_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text', 'instructions' => 'Small uppercase tag above the headline. Include any leading glyph (e.g. "● The ontology · v4.2").'],
                            [
                                'key'          => 'field_iiq_ho_headline_segments',
                                'label'        => 'Headline segments',
                                'name'         => 'headline_segments',
                                'type'         => 'repeater',
                                'instructions' => 'Inline segments composing the H1. Toggle Muted to render in var(--fg-tertiary). Include leading/trailing spaces in segment text where needed.',
                                'layout'       => 'table',
                                'button_label' => 'Add segment',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_ho_segment_text',  'label' => 'Text',  'name' => 'text',  'type' => 'text', 'required' => 1],
                                    ['key' => 'field_iiq_ho_segment_muted', 'label' => 'Muted', 'name' => 'muted', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0],
                                ],
                            ],
                            ['key' => 'field_iiq_ho_lede', 'label' => 'Lede paragraph', 'name' => 'lede', 'type' => 'textarea', 'rows' => 6, 'new_lines' => '', 'instructions' => 'Paragraph below the H1 divider — 21px / line-height 1.55 / weight 500.'],
                        ],
                    ],

                    // ─── section_pillars ───
                    'layout_iiq_section_pillars' => [
                        'key'        => 'layout_iiq_section_pillars',
                        'name'       => 'section_pillars',
                        'label'      => 'Section · Pillars',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('pillars'),
                            ['key' => 'field_iiq_sp_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            ['key' => 'field_iiq_sp_headline', 'label' => 'Headline', 'name' => 'headline', 'type' => 'text'],
                            ['key' => 'field_iiq_sp_headline_lead',  'label' => 'Headline lead',  'name' => 'headline_lead',  'type' => 'text'],
                            ['key' => 'field_iiq_sp_headline_gap',   'label' => 'Headline gap',   'name' => 'headline_gap',   'type' => 'text'],
                            ['key' => 'field_iiq_sp_headline_break', 'label' => 'Headline break', 'name' => 'headline_break', 'type' => 'true_false'],
                            ['key' => 'field_iiq_sp_compact_top',    'label' => 'Compact top (no top padding)', 'name' => 'compact_top', 'type' => 'true_false', 'instructions' => 'Set true when this section visually continues from the previous one (e.g. CoreEntities split across stats + pillars).'],
                            ['key' => 'field_iiq_sp_hide_index',     'label' => 'Hide per-card index numbers', 'name' => 'hide_index', 'type' => 'true_false', 'instructions' => 'When true, omit the 01/02 mono number above each card title (e.g. ArchSecurity grid where cards are just title + body).'],
                            [
                                'key' => 'field_iiq_sp_headline_align',
                                'label' => 'Headline alignment',
                                'name' => 'headline_align',
                                'type' => 'select',
                                'choices' => ['center' => 'Centered', 'left' => 'Left-aligned (wide)'],
                                'default_value' => 'center',
                                'return_format' => 'value',
                            ],
                            ['key' => 'field_iiq_sp_intro', 'label' => 'Intro', 'name' => 'intro', 'type' => 'textarea', 'rows' => 3],
                            [
                                'key' => 'field_iiq_sp_columns',
                                'label' => 'Columns',
                                'name' => 'columns',
                                'type' => 'select',
                                'choices' => ['2' => '2', '3' => '3', '4' => '4'],
                                'default_value' => '3',
                                'return_format' => 'value',
                            ],
                            [
                                'key' => 'field_iiq_sp_style',
                                'label' => 'Render style',
                                'name' => 'style',
                                'type' => 'select',
                                'choices' => [
                                    'cards'        => 'Cards (rounded white card)',
                                    'bars'         => 'Tapered SVG bars (top + bottom)',
                                    'bordered'     => 'Border-separated columns (vertical dividers)',
                                    'top-border'   => 'Top border only (minimal)',
                                    'grid-divided' => 'Grid-divided (1px gap, sharp edges)',
                                ],
                                'default_value' => 'cards',
                                'return_format' => 'value',
                            ],
                            [
                                'key'          => 'field_iiq_sp_items',
                                'label'        => 'Items',
                                'name'         => 'items',
                                'type'         => 'repeater',
                                'button_label' => 'Add pillar',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_sp_item_index', 'label' => 'Index number', 'name' => 'index_number', 'type' => 'text'],
                                    ['key' => 'field_iiq_sp_item_title', 'label' => 'Title', 'name' => 'title', 'type' => 'text'],
                                    ['key' => 'field_iiq_sp_item_eyebrow_label', 'label' => 'Eyebrow label', 'name' => 'eyebrow_label', 'type' => 'text', 'instructions' => 'Optional short caption appended to the per-card eyebrow after an em-dash, e.g. "Data" → renders as "01 — Data".'],
                                    ['key' => 'field_iiq_sp_item_body', 'label' => 'Body', 'name' => 'body', 'type' => 'textarea', 'rows' => 4],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_sp_scenarios_layout',
                                'label' => 'Scenarios layout',
                                'name' => 'scenarios_layout',
                                'type' => 'select',
                                'choices' => ['cards' => 'Sunken cards (default)', 'pullquote' => 'Centered pull-quote with horizontal-line eyebrow'],
                                'default_value' => 'cards',
                                'return_format' => 'value',
                            ],
                            [
                                'key'          => 'field_iiq_sp_scenarios',
                                'label'        => 'Scenarios (optional)',
                                'name'         => 'scenarios',
                                'type'         => 'repeater',
                                'instructions' => 'Optional tagged anecdote cards rendered below the pillars grid.',
                                'button_label' => 'Add scenario',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_sp_scenario_tag',  'label' => 'Tag',  'name' => 'tag',  'type' => 'text'],
                                    ['key' => 'field_iiq_sp_scenario_body', 'label' => 'Body', 'name' => 'body', 'type' => 'textarea', 'rows' => 3],
                                ],
                            ],
                        ],
                    ],

                    // ─── section_split ───
                    'layout_iiq_section_split' => [
                        'key'        => 'layout_iiq_section_split',
                        'name'       => 'section_split',
                        'label'      => 'Section · Split',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('split'),
                            ['key' => 'field_iiq_ss_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            ['key' => 'field_iiq_ss_headline',      'label' => 'Headline',      'name' => 'headline',      'type' => 'text'],
                            ['key' => 'field_iiq_ss_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_ss_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text'],
                            ['key' => 'field_iiq_ss_body',          'label' => 'Body',          'name' => 'body',          'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            [
                                'key' => 'field_iiq_ss_media_slot',
                                'label' => 'Media slot',
                                'name' => 'media_slot',
                                'type' => 'select',
                                'choices' => ['diagram' => 'Diagram', 'image' => 'Image'],
                                'default_value' => 'diagram',
                                'return_format' => 'value',
                            ],
                            [
                                'key' => 'field_iiq_ss_diagram_key',
                                'label' => 'Diagram',
                                'name' => 'diagram_key',
                                'type' => 'select',
                                'choices' => $iiq_diagram_choices,
                                'return_format' => 'value',
                                'conditional_logic' => [[
                                    ['field' => 'field_iiq_ss_media_slot', 'operator' => '==', 'value' => 'diagram'],
                                ]],
                            ],
                            [
                                'key' => 'field_iiq_ss_image',
                                'label' => 'Image',
                                'name' => 'image',
                                'type' => 'image',
                                'return_format' => 'array',
                                'conditional_logic' => [[
                                    ['field' => 'field_iiq_ss_media_slot', 'operator' => '==', 'value' => 'image'],
                                ]],
                            ],
                            ['key' => 'field_iiq_ss_reverse', 'label' => 'Reverse', 'name' => 'reverse', 'type' => 'true_false', 'ui' => 1],
                        ],
                    ],

                    // ─── section_stacked_prose_diagram ───
                    'layout_iiq_section_stacked_prose_diagram' => [
                        'key'        => 'layout_iiq_section_stacked_prose_diagram',
                        'name'       => 'section_stacked_prose_diagram',
                        'label'      => 'Section: Stacked Prose + Diagram',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_sspd_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            ['key' => 'field_iiq_sspd_headline', 'label' => 'Headline', 'name' => 'headline', 'type' => 'text'],
                            ['key' => 'field_iiq_sspd_body', 'label' => 'Body', 'name' => 'body', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            ['key' => 'field_iiq_sspd_diagram_type', 'label' => 'Diagram type', 'name' => 'diagram_type', 'type' => 'text', 'instructions' => 'Slug under template-parts/diagrams/ (e.g. arch-ontology, platform-stack, stack, operator-stack, boundary)'],
                        ],
                    ],

                    // ─── section_gap_split ───
                    'layout_iiq_section_gap_split' => [
                        'key'        => 'layout_iiq_section_gap_split',
                        'name'       => 'section_gap_split',
                        'label'      => 'Section: Gap-Split',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('gap_split'),
                            ['key' => 'field_iiq_sgs_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            ['key' => 'field_iiq_sgs_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_sgs_headline_gap', 'label' => 'Headline gap', 'name' => 'headline_gap', 'type' => 'text'],
                            ['key' => 'field_iiq_sgs_body_left', 'label' => 'Body — left column', 'name' => 'body_left', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            ['key' => 'field_iiq_sgs_body_right', 'label' => 'Body — right column', 'name' => 'body_right', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            ['key' => 'field_iiq_sgs_pull_quote', 'label' => 'Pull quote', 'name' => 'pull_quote', 'type' => 'text'],
                            [
                                'key' => 'field_iiq_sgs_diagram_key',
                                'label' => 'Diagram',
                                'name' => 'diagram_key',
                                'type' => 'select',
                                'choices' => $iiq_diagram_choices,
                                'return_format' => 'value',
                                'allow_null' => 1,
                            ],
                        ],
                    ],

                    // ─── section_stats ───
                    'layout_iiq_section_stats' => [
                        'key'        => 'layout_iiq_section_stats',
                        'name'       => 'section_stats',
                        'label'      => 'Section · Stats',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('stats'),
                            ['key' => 'field_iiq_sst_headline',      'label' => 'Headline',      'name' => 'headline',      'type' => 'text'],
                            ['key' => 'field_iiq_sst_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_sst_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text'],
                            ['key' => 'field_iiq_sst_intro',         'label' => 'Intro',         'name' => 'intro',         'type' => 'textarea', 'rows' => 3],
                            [
                                'key' => 'field_iiq_sst_headline_align',
                                'label' => 'Headline alignment',
                                'name' => 'headline_align',
                                'type' => 'select',
                                'choices' => ['center' => 'Centered', 'left' => 'Left-aligned (wide)'],
                                'default_value' => 'center',
                                'return_format' => 'value',
                            ],
                            [
                                'key' => 'field_iiq_sst_style',
                                'label' => 'Render style',
                                'name' => 'style',
                                'type' => 'select',
                                'choices' => [
                                    'cards' => 'Cards (rounded white)',
                                    'bare'  => 'Bare grid (no border / no card)',
                                ],
                                'default_value' => 'cards',
                                'return_format' => 'value',
                            ],
                            $iiq_stats_repeater('sst', true),
                        ],
                    ],

                    // ─── section_stack (steps / pipeline) ───
                    'layout_iiq_section_stack' => [
                        'key'        => 'layout_iiq_section_stack',
                        'name'       => 'section_stack',
                        'label'      => 'Section · Stack',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('stack'),
                            ['key' => 'field_iiq_sk_eyebrow',       'label' => 'Eyebrow',       'name' => 'eyebrow',       'type' => 'text'],
                            ['key' => 'field_iiq_sk_headline',      'label' => 'Headline',      'name' => 'headline',      'type' => 'text'],
                            ['key' => 'field_iiq_sk_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_sk_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text'],
                            ['key' => 'field_iiq_sk_body',          'label' => 'Body',          'name' => 'body',          'type' => 'textarea', 'rows' => 3],
                            [
                                'key' => 'field_iiq_sk_layout',
                                'label' => 'Layout',
                                'name' => 'layout',
                                'type' => 'select',
                                'choices' => [
                                    'list-2col'          => 'Vertical list (default)',
                                    'timeline-horizontal' => 'Horizontal timeline (4-col with bullets)',
                                    'grid-3col'          => 'Vertical 3-column rows (label / title / body)',
                                ],
                                'default_value' => 'list-2col',
                                'return_format' => 'value',
                            ],
                            [
                                'key'          => 'field_iiq_sk_items',
                                'label'        => 'Items',
                                'name'         => 'items',
                                'type'         => 'repeater',
                                'button_label' => 'Add step',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_sk_item_step', 'label' => 'Step', 'name' => 'step', 'type' => 'text'],
                                    ['key' => 'field_iiq_sk_item_title', 'label' => 'Title', 'name' => 'title', 'type' => 'text'],
                                    ['key' => 'field_iiq_sk_item_body', 'label' => 'Body', 'name' => 'body', 'type' => 'textarea', 'rows' => 3],
                                ],
                            ],
                        ],
                    ],

                    // ─── section_prose ───
                    'layout_iiq_section_prose' => [
                        'key'        => 'layout_iiq_section_prose',
                        'name'       => 'section_prose',
                        'label'      => 'Section · Prose',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('prose'),
                            ['key' => 'field_iiq_pr_eyebrow',       'label' => 'Eyebrow',       'name' => 'eyebrow',       'type' => 'text'],
                            ['key' => 'field_iiq_pr_headline',      'label' => 'Headline',      'name' => 'headline',      'type' => 'text'],
                            ['key' => 'field_iiq_pr_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_pr_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text'],
                            [
                                'key' => 'field_iiq_pr_style',
                                'label' => 'Layout style',
                                'name' => 'style',
                                'type' => 'select',
                                'choices' => [
                                    'single' => 'Single column (default, centered)',
                                    'split'  => 'Split (headline left, paragraphs right; last paragraph as monospace stamp)',
                                ],
                                'default_value' => 'single',
                                'return_format' => 'value',
                            ],
                            [
                                'key'          => 'field_iiq_pr_paragraphs',
                                'label'        => 'Paragraphs',
                                'name'         => 'paragraphs',
                                'type'         => 'repeater',
                                'button_label' => 'Add paragraph',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_pr_p', 'label' => 'Paragraph', 'name' => 'paragraph', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                                ],
                            ],
                        ],
                    ],

                    // ─── section_contrast ───
                    // 2-col contrast table with optional intro paragraphs.
                    // Used by the "Headless by design" section on /how-it-works/.
                    'layout_iiq_section_contrast' => [
                        'key'        => 'layout_iiq_section_contrast',
                        'name'       => 'section_contrast',
                        'label'      => 'Section · Contrast table',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('contrast'),
                            ['key' => 'field_iiq_ct_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            ['key' => 'field_iiq_ct_headline', 'label' => 'Headline', 'name' => 'headline', 'type' => 'text'],
                            ['key' => 'field_iiq_ct_headline_accent', 'label' => 'Headline accent (muted tail)', 'name' => 'headline_accent', 'type' => 'text', 'instructions' => 'Optional. Renders in fg-tertiary after the main headline.'],
                            ['key' => 'field_iiq_ct_body_left', 'label' => 'Body — left column', 'name' => 'body_left', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            ['key' => 'field_iiq_ct_body_right', 'label' => 'Body — right column', 'name' => 'body_right', 'type' => 'wysiwyg', 'media_upload' => 0, 'tabs' => 'visual'],
                            ['key' => 'field_iiq_ct_header_old', 'label' => 'Table header — old column', 'name' => 'table_header_old', 'type' => 'text', 'default_value' => 'The old model'],
                            ['key' => 'field_iiq_ct_header_new', 'label' => 'Table header — new column', 'name' => 'table_header_new', 'type' => 'text', 'default_value' => 'The new model'],
                            [
                                'key'          => 'field_iiq_ct_rows',
                                'label'        => 'Rows',
                                'name'         => 'rows',
                                'type'         => 'repeater',
                                'button_label' => 'Add row',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_ct_row_old', 'label' => 'Old', 'name' => 'old_text', 'type' => 'text'],
                                    ['key' => 'field_iiq_ct_row_new', 'label' => 'New', 'name' => 'new_text', 'type' => 'text'],
                                ],
                            ],
                        ],
                    ],

                    // ─── intelligence_surfaces ───
                    // Wave V-1 — Home "Intelligence finds you" 6-surface
                    // section. The host partial
                    // (template-parts/sections/intelligence-surfaces.php)
                    // ships with hardcoded fallbacks pulled from
                    // exports/latest/js/IntelligenceSurfaces.jsx, so seeding
                    // the surfaces repeater is optional — leave it empty
                    // and the partial renders the canonical 6 surfaces.
                    'layout_iiq_intelligence_surfaces' => [
                        'key'        => 'layout_iiq_intelligence_surfaces',
                        'name'       => 'intelligence_surfaces',
                        'label'      => 'Section · Intelligence surfaces',
                        'display'    => 'block',
                        'sub_fields' => [
                            ['key' => 'field_iiq_isurf_eyebrow',       'label' => 'Eyebrow',       'name' => 'eyebrow',       'type' => 'text'],
                            ['key' => 'field_iiq_isurf_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text'],
                            ['key' => 'field_iiq_isurf_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text'],
                            ['key' => 'field_iiq_isurf_body',          'label' => 'Body',          'name' => 'body',          'type' => 'textarea', 'rows' => 3],
                            [
                                'key'          => 'field_iiq_isurf_surfaces',
                                'label'        => 'Surfaces (leave empty to use export defaults)',
                                'name'         => 'surfaces',
                                'type'         => 'repeater',
                                'button_label' => 'Add surface',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_isurf_tool',    'label' => 'Tool',    'name' => 'tool',    'type' => 'text', 'instructions' => 'e.g. Slack, Gmail, Excel, iMessage, Claude, ChatGPT'],
                                    ['key' => 'field_iiq_isurf_role',    'label' => 'Role',    'name' => 'role',    'type' => 'text', 'instructions' => 'e.g. Operations lead, CEO, CFO'],
                                    ['key' => 'field_iiq_isurf_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text'],
                                    ['key' => 'field_iiq_isurf_body',    'label' => 'Body',    'name' => 'body',    'type' => 'textarea', 'rows' => 4],
                                    [
                                        'key' => 'field_iiq_isurf_mockup',
                                        'label' => 'Mockup slug',
                                        'name' => 'mockup',
                                        'type' => 'select',
                                        'choices' => [
                                            'slack'    => 'slack',
                                            'gmail'    => 'gmail',
                                            'excel'    => 'excel',
                                            'imessage' => 'imessage',
                                            'claude'   => 'claude',
                                            'chatgpt'  => 'chatgpt',
                                        ],
                                        'default_value' => 'slack',
                                        'return_format' => 'value',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // ─── section_team ───
                    'layout_iiq_section_team' => [
                        'key'        => 'layout_iiq_section_team',
                        'name'       => 'section_team',
                        'label'      => 'Section · Team',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('team'),
                            ['key' => 'field_iiq_tm_headline',      'label' => 'Headline',      'name' => 'headline',      'type' => 'text'],
                            ['key' => 'field_iiq_tm_headline_lead', 'label' => 'Headline lead', 'name' => 'headline_lead', 'type' => 'text', 'instructions' => 'Stacked headline lines (HTML <br> allowed). E.g. "Operators.<br>Innovators."'],
                            ['key' => 'field_iiq_tm_headline_gap',  'label' => 'Headline gap',  'name' => 'headline_gap',  'type' => 'text', 'instructions' => 'Final headline line, rendered in tertiary/muted colour. E.g. "Architects."'],
                            ['key' => 'field_iiq_tm_lede',          'label' => 'Lede',          'name' => 'lede',          'type' => 'textarea', 'rows' => 4, 'instructions' => 'Optional intro paragraph. When set, the header renders as a two-column layout (headline left, lede right).'],
                            ['key' => 'field_iiq_tm_featured',      'label' => 'Featured count','name' => 'featured_count', 'type' => 'number', 'default_value' => 0, 'instructions' => 'How many members render in the top row (3-up, large). Remaining members render in a centered 2-up row. 0 = single uniform 3-col grid.'],
                            [
                                'key' => 'field_iiq_tm_avatar_style',
                                'label' => 'Avatar style',
                                'name' => 'avatar_style',
                                'type' => 'select',
                                'choices' => [
                                    'circle' => 'Circle (current default)',
                                    'square' => 'Square gradient (64×64, border-radius 6)',
                                ],
                                'default_value' => 'circle',
                                'return_format' => 'value',
                            ],
                            [
                                'key'          => 'field_iiq_tm_members',
                                'label'        => 'Members',
                                'name'         => 'members',
                                'type'         => 'repeater',
                                'button_label' => 'Add member',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_tm_name', 'label' => 'Name', 'name' => 'name', 'type' => 'text'],
                                    ['key' => 'field_iiq_tm_role', 'label' => 'Role', 'name' => 'role', 'type' => 'text'],
                                    ['key' => 'field_iiq_tm_credential', 'label' => 'Credential', 'name' => 'credential', 'type' => 'text', 'instructions' => 'One-line credential, rendered as a bold display lead above the bio.'],
                                    ['key' => 'field_iiq_tm_bio', 'label' => 'Bio', 'name' => 'bio', 'type' => 'textarea', 'rows' => 4],
                                    ['key' => 'field_iiq_tm_photo', 'label' => 'Photo', 'name' => 'photo', 'type' => 'image', 'return_format' => 'array', 'instructions' => 'Optional. Overrides the bundled theme headshot if set.'],
                                    ['key' => 'field_iiq_tm_photo_slug', 'label' => 'Photo slug', 'name' => 'photo_slug', 'type' => 'text', 'instructions' => 'Filename (no extension) of a bundled headshot in assets/img/team/. E.g. "scott" → assets/img/team/scott.jpg. Used when no Photo is uploaded above.'],
                                ],
                            ],
                        ],
                    ],

                    // ─── trust_logos ───
                    'layout_iiq_trust_logos' => [
                        'key'        => 'layout_iiq_trust_logos',
                        'name'       => 'trust_logos',
                        'label'      => 'Trust · Logos',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('trust_logos'),
                            ['key' => 'field_iiq_tl_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
                            [
                                'key'          => 'field_iiq_tl_logos',
                                'label'        => 'Logos',
                                'name'         => 'logos',
                                'type'         => 'repeater',
                                'button_label' => 'Add logo',
                                'sub_fields'   => [
                                    ['key' => 'field_iiq_tl_image', 'label' => 'Image', 'name' => 'image', 'type' => 'image', 'return_format' => 'array'],
                                    ['key' => 'field_iiq_tl_alt', 'label' => 'Alt', 'name' => 'alt', 'type' => 'text'],
                                    ['key' => 'field_iiq_tl_url', 'label' => 'URL', 'name' => 'url', 'type' => 'text'],
                                ],
                            ],
                        ],
                    ],

                    // ─── trust_quote ───
                    'layout_iiq_trust_quote' => [
                        'key'        => 'layout_iiq_trust_quote',
                        'name'       => 'trust_quote',
                        'label'      => 'Trust · Quote',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('trust_quote'),
                            ['key' => 'field_iiq_tq_quote', 'label' => 'Quote', 'name' => 'quote', 'type' => 'textarea', 'rows' => 4],
                            ['key' => 'field_iiq_tq_role', 'label' => 'Attribution role', 'name' => 'attribution_role', 'type' => 'text'],
                            ['key' => 'field_iiq_tq_status', 'label' => 'Attribution status', 'name' => 'attribution_status', 'type' => 'text'],
                            ['key' => 'field_iiq_tq_dark', 'label' => 'Dark', 'name' => 'dark', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1],
                        ],
                    ],

                    // ─── cta_banner ───
                    'layout_iiq_cta_banner' => [
                        'key'        => 'layout_iiq_cta_banner',
                        'name'       => 'cta_banner',
                        'label'      => 'CTA · Banner',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('cta_banner'),
                            ['key' => 'field_iiq_cb_eyebrow',  'label' => 'Eyebrow',  'name' => 'eyebrow',  'type' => 'text'],
                            ['key' => 'field_iiq_cb_headline', 'label' => 'Headline', 'name' => 'headline', 'type' => 'text'],
                            ['key' => 'field_iiq_cb_body',     'label' => 'Body',     'name' => 'body',     'type' => 'textarea', 'rows' => 3],
                            $iiq_cta_group('cb_primary', 'Primary CTA', 'primary_cta'),
                            $iiq_cta_group('cb_secondary', 'Secondary CTA', 'secondary_cta'),
                            [
                                'key' => 'field_iiq_cb_variant',
                                'label' => 'Variant',
                                'name' => 'variant',
                                'type' => 'select',
                                'choices' => ['dark' => 'Dark', 'light' => 'Light'],
                                'default_value' => 'dark',
                                'return_format' => 'value',
                            ],
                        ],
                    ],

                    // ─── diagram ───
                    'layout_iiq_diagram' => [
                        'key'        => 'layout_iiq_diagram',
                        'name'       => 'diagram',
                        'label'      => 'Diagram',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('diagram'),
                            [
                                'key' => 'field_iiq_dg_diagram_key',
                                'label' => 'Diagram',
                                'name' => 'diagram_key',
                                'type' => 'select',
                                'choices' => $iiq_diagram_choices,
                                'return_format' => 'value',
                            ],
                            ['key' => 'field_iiq_dg_caption', 'label' => 'Caption', 'name' => 'caption', 'type' => 'text'],
                            // Extended fields for stack / platform-stack
                            [
                                'key' => 'field_iiq_dg_index',
                                'label' => 'Index',
                                'name' => 'index',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_label',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_header_label',
                                'label' => 'Header label',
                                'name' => 'header_label',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_headline_left',
                                'label' => 'Headline (left)',
                                'name' => 'headline_left',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_headline_right',
                                'label' => 'Headline (right)',
                                'name' => 'headline_right',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_body',
                                'label' => 'Body',
                                'name' => 'body',
                                'type' => 'textarea',
                                'rows' => 4,
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                            [
                                'key' => 'field_iiq_dg_closing_line',
                                'label' => 'Closing line',
                                'name' => 'closing_line',
                                'type' => 'text',
                                'conditional_logic' => [
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'stack']],
                                    [['field' => 'field_iiq_dg_diagram_key', 'operator' => '==', 'value' => 'platform-stack']],
                                ],
                            ],
                        ],
                    ],

                    // ─── form ───
                    'layout_iiq_form' => [
                        'key'        => 'layout_iiq_form',
                        'name'       => 'form',
                        'label'      => 'Form',
                        'display'    => 'block',
                        'sub_fields' => [
                            $iiq_settings_group_for('form'),
                            [
                                'key' => 'field_iiq_fm_form_type',
                                'label' => 'Form type',
                                'name' => 'form_type',
                                'type' => 'select',
                                'choices' => ['contact' => 'Contact', 'signin' => 'Sign in'],
                                'default_value' => 'contact',
                                'return_format' => 'value',
                            ],
                        ],
                    ],

                ],
            ],
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
        ]],
        'menu_order'      => 0,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'hide_on_screen'  => $iiq_hide_on_screen,
        'active'          => true,
    ]);

    // ──────────────────────────────────────────────────────────────────
    // GROUP B — PAGE META (SEO)
    // ──────────────────────────────────────────────────────────────────
    acf_add_local_field_group([
        'key'    => 'group_iiq_page_meta',
        'title'  => 'Page Meta',
        'fields' => [
            ['key' => 'field_iiq_meta_title', 'label' => 'Meta title', 'name' => 'meta_title', 'type' => 'text'],
            ['key' => 'field_iiq_meta_desc', 'label' => 'Meta description', 'name' => 'meta_description', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'field_iiq_meta_og', 'label' => 'OG image', 'name' => 'og_image', 'type' => 'image', 'return_format' => 'array'],
            [
                'key' => 'field_iiq_meta_schema',
                'label' => 'Schema override (JSON-LD)',
                'name' => 'schema_override',
                'type' => 'textarea',
                'rows' => 8,
                'instructions' => 'Optional. Raw JSON-LD inserted into <head>.',
            ],
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
        ]],
        'menu_order'      => 10,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'hide_on_screen'  => $iiq_hide_on_screen,
        'active'          => true,
    ]);

    // ──────────────────────────────────────────────────────────────────
    // GROUP C — SITE SETTINGS (Options Page)
    // ──────────────────────────────────────────────────────────────────
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'IgniteIQ Site Settings',
            'menu_title' => 'Site Settings',
            'menu_slug'  => 'iiq_site_settings',
            'capability' => 'manage_options',
            'redirect'   => false,
        ]);
    }

    acf_add_local_field_group([
        'key'    => 'group_iiq_site_settings',
        'title'  => 'Site Settings',
        'fields' => [
            // Header CTA
            [
                'key'    => 'field_iiq_settings_header_cta',
                'label'  => 'Header CTA',
                'name'   => 'header_cta',
                'type'   => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    ['key' => 'field_iiq_settings_header_cta_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text'],
                    ['key' => 'field_iiq_settings_header_cta_url', 'label' => 'URL', 'name' => 'url', 'type' => 'text'],
                ],
            ],

            // Footer columns
            [
                'key'   => 'field_iiq_settings_footer_columns',
                'label' => 'Footer columns',
                'name'  => 'footer_columns',
                'type'  => 'repeater',
                'button_label' => 'Add column',
                'sub_fields' => [
                    ['key' => 'field_iiq_settings_fc_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text'],
                    [
                        'key'          => 'field_iiq_settings_fc_links',
                        'label'        => 'Links',
                        'name'         => 'links',
                        'type'         => 'repeater',
                        'button_label' => 'Add link',
                        'sub_fields'   => [
                            ['key' => 'field_iiq_settings_fc_link_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text'],
                            ['key' => 'field_iiq_settings_fc_link_url', 'label' => 'URL', 'name' => 'url', 'type' => 'text'],
                        ],
                    ],
                ],
            ],

            ['key' => 'field_iiq_settings_footer_copyright', 'label' => 'Footer copyright note', 'name' => 'footer_copyright_note', 'type' => 'text'],
            [
                'key'           => 'field_iiq_settings_contact_email',
                'label'         => 'Contact form recipients',
                'name'          => 'contact_email',
                'type'          => 'text',
                'instructions'  => 'Email address(es) that receive contact-form submissions. Separate multiple addresses with commas. Leave blank to fall back to the built-in default list.',
                'default_value' => 'scott@igniteiq.com, matt@igniteiq.com, joshscott@igniteiq.com',
            ],
            ['key' => 'field_iiq_settings_contact_phone', 'label' => 'Contact phone', 'name' => 'contact_phone', 'type' => 'text'],

            // Social links
            [
                'key'   => 'field_iiq_settings_social_links',
                'label' => 'Social links',
                'name'  => 'social_links',
                'type'  => 'repeater',
                'button_label' => 'Add social link',
                'sub_fields' => [
                    [
                        'key' => 'field_iiq_settings_social_platform',
                        'label' => 'Platform',
                        'name' => 'platform',
                        'type' => 'select',
                        'choices' => [
                            'linkedin' => 'LinkedIn',
                            'twitter'  => 'X / Twitter',
                            'youtube'  => 'YouTube',
                            'github'   => 'GitHub',
                            'instagram' => 'Instagram',
                        ],
                        'return_format' => 'value',
                    ],
                    ['key' => 'field_iiq_settings_social_url', 'label' => 'URL', 'name' => 'url', 'type' => 'text'],
                ],
            ],

            // Nav announcement bar
            [
                'key'    => 'field_iiq_settings_announcement',
                'label'  => 'Nav announcement bar',
                'name'   => 'nav_announcement_bar',
                'type'   => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    ['key' => 'field_iiq_settings_announcement_text', 'label' => 'Text', 'name' => 'text', 'type' => 'text'],
                    ['key' => 'field_iiq_settings_announcement_url', 'label' => 'URL', 'name' => 'url', 'type' => 'text'],
                    ['key' => 'field_iiq_settings_announcement_active', 'label' => 'Active', 'name' => 'active', 'type' => 'true_false', 'ui' => 1],
                ],
            ],
        ],
        'location' => [[
            ['param' => 'options_page', 'operator' => '==', 'value' => 'iiq_site_settings'],
        ]],
        'menu_order'      => 0,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'hide_on_screen'  => $iiq_hide_on_screen,
        'active'          => true,
    ]);

}
