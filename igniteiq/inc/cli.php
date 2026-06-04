<?php
/**
 * WP-CLI command: wp igniteiq:seed
 *
 * Seeds default ACF flexible-content rows for the IgniteIQ marketing site
 * (front-page, how-it-works, ontology, company, contact, signin).
 *
 * Usage:
 *   wp igniteiq seed
 *   wp igniteiq seed --force
 *   wp igniteiq seed --page=ontology
 *
 * @package IgniteIQ
 */

if (!defined('ABSPATH')) { exit; }

if (!class_exists('IgniteIQ_CLI')) {

    /**
     * IgniteIQ content seeder.
     *
     * Defined unconditionally so the admin migration tool can call
     * `IgniteIQ_CLI::default_pages()` outside of a WP-CLI context. The
     * `seed()` method uses WP_CLI helpers and is only safe to call when
     * WP_CLI is loaded.
     */
    class IgniteIQ_CLI {

        /**
         * Seed default page sections.
         *
         * ## OPTIONS
         *
         * [--force]
         * : Overwrite existing page_sections content.
         *
         * [--page=<slug>]
         * : Limit to a single slug (home|how-it-works|ontology|company|contact|signin).
         *
         * @when after_wp_load
         */
        public function seed($args, $assoc_args) {
            if (!function_exists('update_field')) {
                WP_CLI::error('ACF Pro is not active. Aborting.');
                return;
            }

            $force      = !empty($assoc_args['force']);
            $only_slug  = isset($assoc_args['page']) ? sanitize_key($assoc_args['page']) : null;
            $pages      = self::default_pages();

            foreach ($pages as $slug => $rows) {
                if ($only_slug && $slug !== $only_slug) {
                    continue;
                }

                $page_id = $this->find_or_create_page($slug);
                if (!$page_id) {
                    WP_CLI::warning("Could not resolve page for slug: {$slug}");
                    continue;
                }

                $existing = get_field('page_sections', $page_id);
                if (!empty($existing) && !$force) {
                    WP_CLI::warning("Skipping '{$slug}' (id {$page_id}) — already has page_sections. Use --force to overwrite.");
                    continue;
                }

                // Force-mode: wipe all page_sections meta first so ACF writes
                // a fresh row set rather than diff-merging against stale meta
                // (which silently no-ops when sub-field keys/names changed).
                if ($force) {
                    delete_field('page_sections', $page_id);
                    // Belt-and-suspenders: nuke any leftover page_sections_* meta
                    // rows that ACF's own delete may not catch when field schema
                    // renamed sub-fields (e.g. _settings → settings).
                    global $wpdb;
                    $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'page_sections_%%'",
                        $page_id
                    ));
                }

                $ok = update_field('page_sections', $rows, $page_id);
                if ($ok) {
                    WP_CLI::success("Seeded '{$slug}' (id {$page_id}) with " . count($rows) . ' sections.');
                } else {
                    WP_CLI::warning("update_field returned falsy for '{$slug}' (id {$page_id}).");
                }
            }

            WP_CLI::log('Done.');
        }

        /**
         * Resolve page slug → page ID. For 'home', return show_on_front page.
         * Creates a page if missing.
         */
        private function find_or_create_page($slug) {
            if ($slug === 'home') {
                $front = (int) get_option('page_on_front');
                if ($front) {
                    return $front;
                }
                $existing = get_page_by_path('home');
                if ($existing) {
                    return $existing->ID;
                }
                $id = wp_insert_post([
                    'post_title'   => 'Home',
                    'post_name'    => 'home',
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                ]);
                if (!is_wp_error($id)) {
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $id);
                    return $id;
                }
                return 0;
            }

            // Look in any post status so a draft 'ride-along' is reused
            // instead of being recreated as a duplicate publish.
            $page = get_page_by_path($slug, OBJECT, 'page');
            if (!$page) {
                $hits = get_posts([
                    'name'        => $slug,
                    'post_type'   => 'page',
                    'post_status' => 'any',
                    'numberposts' => 1,
                ]);
                $page = $hits ? $hits[0] : null;
            }
            if ($page) {
                return $page->ID;
            }
            $title = ucwords(str_replace('-', ' ', $slug));
            $id = wp_insert_post([
                'post_title'  => $title,
                'post_name'   => $slug,
                'post_type'   => 'page',
                'post_status' => self::default_post_status_for_slug($slug),
            ]);
            return is_wp_error($id) ? 0 : $id;
        }

        /**
         * The whole content payload, by slug.
         */
        public static function default_pages() {
            return [
                'home'         => self::page_home(),
                'how-it-works' => self::page_how_it_works(),
                'ontology'     => self::page_ontology(),
                'company'      => self::page_company(),
                'contact'      => self::page_contact(),
                'signin'       => self::page_signin(),
                'ride-along'   => self::page_ride_along(),
            ];
        }

        /**
         * Per-slug default WP post_status when a page is auto-created. Slugs
         * not listed here default to 'publish'.
         *
         * Ride-along originally shipped as 'draft' while the Demo.jsx port was
         * in progress. The full page (7 scenes + dashboard + 6 mock surfaces)
         * is now complete and reviewed on staging, so it publishes on create
         * like every other cornerstone page. Note: this only affects pages at
         * CREATE time — find_or_create_page never downgrades an existing
         * page's status, so re-seeding won't unpublish a live page.
         */
        private static function default_post_status_for_slug($slug) {
            $map = [];
            return isset($map[$slug]) ? $map[$slug] : 'publish';
        }

        // ───────────────────────────────────────────────────────────
        // HOME — App.jsx render order (export 20260501-handoff-website-2-4):
        // HeroStatement → WhyItMattersSection (01) → ProblemSection (02)
        // → SolutionSection (03 The solution) → ArchStackDiagram (04 The stack)
        // → WhatChangesSection (05) → InvestInOutcomesSection (06 The path)
        // → DeploymentSection (07) → CTASection
        // ───────────────────────────────────────────────────────────
        private static function page_home() {
            return [
                // HeroStatement (Hero.js, App.js: showStatement=true, statement='decision')
                // Wave V-4 — tagline_choice / variant toggles let WP admins
                // swap headline without touching seed. tagline_choice values
                // are the four export-canonical taglines. headline_lines
                // remains the fidelity fallback for the default option.
                [
                    'acf_fc_layout'  => 'hero_statement',
                    'eyebrow'        => '',
                    'tagline_choice' => 'The Decision Engine for Modern Trades.',
                    'variant'        => 'statement',
                    'headline_lines' => [
                        ['line' => 'The Decision Engine for Modern Trades.'],
                    ],
                    'subhead' => 'Your business makes a thousand decisions a day. IgniteIQ makes every one faster, smarter, and right — running on enterprise-grade infrastructure deployed inside your cloud.',
                    'primary_cta'   => ['label' => '', 'url' => ''],
                    'secondary_cta' => ['label' => '', 'url' => ''],
                    'stats' => [
                        ['value' => '< 7 days', 'label' => 'Time to deployed'],
                        ['value' => '25+',      'label' => 'Systems unified'],
                        ['value' => '100%',     'label' => 'Customer-owned'],
                        ['value' => 'v4.2',     'label' => 'Framework version'],
                    ],
                ],

                // FIDELITY NOTE: the export's home page (App.js) computes a
                // `HeroComp` (HeroEditorial/Cinematic/Split per tweaks.hero) but
                // its JSX return does NOT render that variable — only HeroStatement
                // appears before WhyItMattersSection. So the home page has ONE
                // hero, not two. The "Built by the operators who ran the trucks."
                // headline lives only in the dev tweaks panel as an alternative.

                // WhyItMattersSection (SectionsA.js, index="01" label="Why this matters")
                [
                    'acf_fc_layout' => 'section_prose',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'Why this matters',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => '',
                    'headline' => "The companies that win the next decade won't be the ones with the best software. They’ll be the ones who own their intelligence.",
                    'headline_lead' => "The companies that win the next decade won't be the ones with the best software.",
                    'headline_gap'  => "They’ll be the ones who own their intelligence.",
                    'style'    => 'single',
                    'paragraphs' => [
                        ['paragraph' => '<p>Every advantage in this industry gets copied. A new tool. A new tactic. A new vendor. The infrastructure underneath your business doesn’t. Owning your intelligence is the only advantage that compounds - and the operators who build it will outlast the ones who rent it.</p>'],
                    ],
                ],

                // ProblemSection (SectionsA.js, index="02" label="The problem")
                [
                    'acf_fc_layout' => 'section_gap_split',
                    'settings' => [
                        'section_index' => '02',
                        'section_label' => 'The problem',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'       => '',
                    'headline_lead' => 'Every home services business is a decision-making engine.',
                    'headline_gap'  => 'The data underneath is blurry.',
                    'body_left'     => '<p>A $15M HVAC company makes thousands of micro-decisions a day — which job to dispatch, which lead to call back, which technician to send where. The compounding quality of those decisions separates a 12% margin company from a 22% margin company.</p>',
                    'body_right'    => '<p>Today those decisions are made on data spread across 15 to 25 disconnected systems — siloed, duplicated, often contradictory. The AI tools sold into this market promise to fix it and consistently fail. The foundation underneath was never ready.</p>',
                    'pull_quote'    => 'AI without clean, structured data is expensive guessing.',
                    'diagram_key'   => 'operator-stack',
                ],

                // SolutionSection (SectionsA.jsx, index="03" label="The solution")
                // FIDELITY EXCEPTION: The export's SolutionSection ships a
                // bespoke 3-card layout with kicker (Data/Logic/Actions),
                // headline, body, and a per-card "Components" bullet list,
                // plus a colored top-strip on layer 02. This port renders it
                // through `section_pillars` with style="bordered" + per-item
                // `eyebrow_label`, with the components list embedded into
                // each item's `body` HTML. The red top-strip on layer 02 and
                // the "↓ Next — Each layer is real infrastructure…" closing
                // transition line are not yet ported (follow-up: introduce a
                // dedicated `section_solution` ACF layout + template-part).
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '03',
                        'section_label' => 'The solution',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => 'What you actually get',
                    'headline' => '',
                    'headline_lead' => 'Real data infrastructure that turns blurry data into',
                    'headline_gap'  => 'faster, sharper decisions.',
                    'intro'    => 'A unified data foundation, a proprietary ontology that gives it meaning, and AI products that act on it. Each layer is operational on its own — together, they replace 15 to 25 disconnected systems with a foundation you can build on for years.',
                    'columns'  => '3',
                    'style'    => 'bordered',
                    'items' => [
                        [
                            'index_number'  => '01',
                            'eyebrow_label' => 'Data',
                            'title' => 'Your data, unified.',
                            'body'  => '<p>An enterprise-grade warehouse stood up inside your cloud. Forty-plus connectors pull every operational source — ServiceTitan, QuickBooks, marketing platforms, payroll, call tracking — into one queryable, cleaned model. No more conflicting reports. No more reconciling spreadsheets. One source of truth, owned by you.</p><p style="margin-top:24px;font-family:var(--font-mono);font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:500;">Components</p><ul style="list-style:none;padding:0;margin:14px 0 0;display:flex;flex-direction:column;gap:10px;font-size:14px;line-height:1.45;color:var(--fg-primary);"><li>· Data warehouse in your cloud</li><li>· 40+ source connectors</li><li>· Cleaning, deduplication, hygiene</li><li>· Security and governance built in</li></ul>',
                        ],
                        [
                            'index_number'  => '02',
                            'eyebrow_label' => 'Logic',
                            'title' => 'The ontology, on top.',
                            'body'  => '<p>The proprietary IgniteIQ ontology resolves your operational entities — jobs, customers, technicians, trucks, invoices — into one shared model. It defines what things are and how they relate, so every analytics query, every agent, and every automation reads from the same logic. This is the layer that makes AI actually work.</p><p style="margin-top:24px;font-family:var(--font-mono);font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:500;">Components</p><ul style="list-style:none;padding:0;margin:14px 0 0;display:flex;flex-direction:column;gap:10px;font-size:14px;line-height:1.45;color:var(--fg-primary);"><li>· Models, logic, and meaning</li><li>· Entity resolution across systems</li><li>· Shared definitions for every product</li><li>· Continuously improving framework</li></ul>',
                        ],
                        [
                            'index_number'  => '03',
                            'eyebrow_label' => 'Actions',
                            'title' => 'Decisions, faster.',
                            'body'  => '<p>Analytics and BI to understand. Workflows and automations to act. Agents that operate inside the ontology and write decisions back into the systems your team already uses. The unit of value isn’t a dashboard — it’s a better decision, made thousands of times a day across your business.</p><p style="margin-top:24px;font-family:var(--font-mono);font-size:10px;letter-spacing:0.22em;text-transform:uppercase;color:var(--fg-tertiary);font-weight:500;">Components</p><ul style="list-style:none;padding:0;margin:14px 0 0;display:flex;flex-direction:column;gap:10px;font-size:14px;line-height:1.45;color:var(--fg-primary);"><li>· Analytics and BI</li><li>· AI Workflows and automations</li><li>· Agentic AI agents with write-back</li><li>· Custom AI products and tools</li></ul>',
                        ],
                    ],
                    'scenarios' => [],
                ],

                // ArchStackDiagram on home (App.jsx: index="04", label="The stack")
                [
                    'acf_fc_layout' => 'diagram',
                    'settings' => [
                        'section_index' => '04',
                        'section_label' => 'The stack',
                        'theme_variant' => 'light',
                    ],
                    'diagram_key'    => 'stack',
                    'caption'        => '',
                    'index'          => '04',
                    'label'          => 'The stack',
                    'header_label'   => 'How it all fits together',
                    'headline_left'  => 'One model of your business.',
                    'headline_right' => 'Every product, every object, every system reads from it.',
                    'body'           => 'IgniteIQ deploys a data warehouse inside your cloud, unifies every operational system into a single ontology, and runs AI products on top of it. The whole stack is built to do one thing - make every decision your business makes sharper than the one before it.',
                    'closing_line'   => 'Every decision — faster, smarter, and right.',
                ],

                // Wave V-1 — IntelligenceSurfaces ("Where intelligence shows up").
                // Source: exports/latest/js/IntelligenceSurfaces.jsx (SURFACES_CONFIG,
                // lines 1034–1083). The host partial ships hardcoded fallbacks pulled
                // from the export so an empty seed renders the canonical 6 surfaces
                // (Slack / Gmail / Excel / iMessage / Claude / ChatGPT) with mock UI
                // chrome (template-parts/mockups/*.php). To customize, add a
                // `surfaces` repeater on the home page in WP admin.
                [
                    'acf_fc_layout' => 'intelligence_surfaces',
                ],

                // FIDELITY EXCEPTION (Wave V-4, 2026-05-28): the
                // "WhatChangesSection" Clarity/Control/Trust pillars block is
                // removed from /home/ to match export
                // 20260528-igniteiq-website-2-5-new. The export folds this
                // story into the new "Intelligence finds you" channel-scenes
                // section (Wave V-1) and the existing stack-diagram closing
                // line "One model of your business. Every product, every
                // object, every system reads from it." (cli.php:316). Block
                // preserved here in case the customer wants it restored —
                // uncomment to bring back.
                /*
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '05',
                        'section_label' => 'What changes for you',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => '',
                    'headline' => '',
                    'headline_lead' => 'What changes when the data',
                    'headline_gap'  => 'actually works.',
                    'intro'    => 'When the data underneath actually works, the business gets simpler to run. Better answers come faster. The second-guessing stops. Operators move with conviction instead of guesswork.',
                    'columns'  => '3',
                    'style'    => 'bars',
                    'items' => [
                        [
                            'index_number' => '01',
                            'title' => 'Clarity',
                            'body'  => 'You finally see what’s actually happening. Which jobs make money. Which technicians are bleeding margin. Which marketing dollars produce revenue and which ones lie to you. One picture of the business that doesn’t change depending on which report you open or which team you ask.',
                        ],
                        [
                            'index_number' => '02',
                            'title' => 'Control',
                            'body'  => 'You stop running the business from yesterday’s reports. The platform surfaces the things hurting you right now — the unbilled work, the at-risk jobs, the calls your CSRs missed — while there’s still time to do something about them. You move from reacting to operating.',
                        ],
                        [
                            'index_number' => '03',
                            'title' => 'Trust',
                            'body'  => 'Every number ties back to the data behind it. Every report agrees with the one next to it. When the answer is on your screen, you don’t have to call someone to verify it. You read it once, you act on it, and you don’t think about it again.',
                        ],
                    ],
                    'scenarios' => [],
                ],
                */

                // InvestInOutcomesSection (InvestInOutcomes.jsx, index="06" label="The path")
                // Per export 20260501: pillars rewritten CLARITY/CONTROL/CONFIDENCE →
                // FOUND MARGIN / CAPTURED REVENUE / FORCE MULTIPLIER. New intro copy.
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '06',
                        'section_label' => 'The path',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => '',
                    'headline' => 'Invest in outcomes. Not experiments.',
                    'headline_lead'  => 'Invest in outcomes.',
                    'headline_gap'   => 'Not experiments.',
                    'headline_break' => true,
                    'intro'    => 'The platform doesn’t just give you better answers. It changes the economic reality of the business — surfacing the margin you’re losing, capturing the revenue already inside your walls, and giving every person on your team the leverage to do their best work.',
                    'columns'  => '3',
                    'style'    => 'bars',
                    'items' => [
                        [
                            'index_number' => '01',
                            'title' => 'FOUND MARGIN',
                            'body'  => 'The money you’re losing right now becomes visible — and recoverable. The unbilled jobs sitting in dispatch. The marketing channels burning cash without producing revenue. The technicians and trucks running below their potential. The maintenance agreements aging out without follow-up. The platform surfaces every leak in the business and gives you a path to close it. Not someday. Inside the first 90 days.',
                        ],
                        [
                            'index_number' => '02',
                            'title' => 'CAPTURED REVENUE',
                            'body'  => 'The revenue already inside the business gets captured. The leads your team didn’t follow up on get followed up on. The customers due for service get reached before your competitor reaches them. The upgrade conversations that should have happened, happen. The platform finds the revenue you’ve been leaving on the table and turns it into closed work — without adding ad spend, without adding pressure, without forcing your team to work harder.',
                        ],
                        [
                            'index_number' => '03',
                            'title' => 'FORCE MULTIPLIER',
                            'body'  => 'Every person on your team becomes more powerful. CSRs answering calls with the full customer history in front of them. Dispatchers seeing the next-best move before they ask. Technicians showing up with context that makes them faster and sharper. Managers operating with one clean picture instead of five disagreeing reports. The platform doesn’t replace your team — it gives them the leverage to do their best work, every day, on every job.',
                        ],
                    ],
                    'scenarios' => [],
                ],

                // DeploymentSection (SectionsA.jsx, index="07" label="Deployment")
                [
                    'acf_fc_layout' => 'section_stack',
                    'settings' => [
                        'section_index' => '07',
                        'section_label' => 'Deployment',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => 'Time to live',
                    'headline' => 'What used to take a year. Shipped in a week.',
                    'headline_lead' => 'What used to take a year.',
                    'headline_gap'  => 'Shipped in a week.',
                    'body'     => 'IgniteIQ deploys real infrastructure - a data warehouse in your cloud, your data unified into the ontology, AI products running on top - in seven days. Not a pilot. Not a slide deck. Production infrastructure from day one.',
                    'layout'   => 'timeline-horizontal',
                    'items' => [
                        ['step' => 'Day 0', 'title' => 'Contract signed',    'body' => 'Workspace provisioned in your cloud.'],
                        ['step' => 'Day 2', 'title' => 'Systems connected',  'body' => '15–25 source systems wired into the warehouse.'],
                        ['step' => 'Day 5', 'title' => 'Ontology activated', 'body' => 'Jobs, leads, technicians, invoices resolved into one model.'],
                        ['step' => 'Day 7', 'title' => 'Live decisions',     'body' => 'Analytics + agents shipping decisions to operators.'],
                    ],
                ],

                // CTASection (SectionsB.js)
                [
                    'acf_fc_layout' => 'cta_banner',
                    'settings' => [
                        'section_index' => '',
                        'section_label' => 'Get started today',
                        'theme_variant' => 'dark',
                    ],
                    'eyebrow'       => 'Get started today',
                    'headline'      => 'Own Your Intelligence.',
                    'body'          => 'Your cloud. Your data. Your Intelligence. Deployed in days, not months - and yours from the moment we ship.',
                    'primary_cta'   => ['label' => 'Get started today', 'url' => '/contact/'],
                    'secondary_cta' => ['label' => 'How it works',      'url' => '/how-it-works/'],
                    'variant'       => 'dark',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // HOW IT WORKS — Architecture.js render order:
        // ArchHero → ArchTwoHalves (01) → PlatformStackDiagram (02)
        // → ArchHeadless (03) → ArchHowItDeploys (04) → ArchSecurity (05)
        // → ArchCTA
        // ───────────────────────────────────────────────────────────
        private static function page_how_it_works() {
            return [
                // ArchHero (Architecture.js)
                [
                    'acf_fc_layout' => 'hero_editorial',
                    'eyebrow'        => 'Architecture · v4.2',
                    // FIDELITY: 4-segment two-tone H1 inline (Architecture.js:96-115).
                    // "Built on" + tertiary " your cloud. " + "Run on" + tertiary " your data."
                    // Caller controls whitespace via leading/trailing spaces inside each line.
                    'headline_inline' => true,
                    'headline_lines' => [
                        ['line' => 'Built on',         'muted' => false],
                        ['line' => ' your cloud. ',   'muted' => true],
                        ['line' => 'Run on',          'muted' => false],
                        ['line' => ' your data.',     'muted' => true],
                    ],
                    'body'          => "<p>The IgniteIQ architecture is built around one premise: your warehouse, your data, and the intelligence layer all run inside your cloud account. Modular, replaceable, and continuously compounding it's intelligence.</p>",
                    'primary_cta'   => ['label' => '', 'url' => ''],
                    'secondary_cta' => ['label' => '', 'url' => ''],
                    'dark'          => true,
                    'size_variant'  => 'compact',
                    'stats' => [
                        ['label' => 'Your cloud',           'value' => 'Enterprise grade cloud infrastructure'],
                        ['label' => 'Your data',            'value' => 'Safe. Unified. Clean. Trusted.'],
                        ['label' => 'Runs in your account', 'value' => 'IgniteIQ Ontology Layer'],
                        ['label' => 'Framework version',    'value' => 'v4.2'],
                    ],
                ],

                // ArchTwoHalves (Architecture.js, index="01" label="How it runs")
                // Renders headline + body + ArchOntologyScene diagram below in vertical stack.
                [
                    'acf_fc_layout' => 'section_stacked_prose_diagram',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'How it runs',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'       => '',
                    'headline'      => 'Your warehouse holds your data. Our intelligence framework runs alongside it.',
                    'body'          => '<p>The IgniteIQ architecture deploys directly into your owned enterprise-grade cloud account — provisioned for you. Every line of business — every job, customer, technician, truck — gets resolved into a single model that lives in your warehouse and is queried by the framework. Your data never leaves your IAM.</p>',
                    'diagram_type'  => 'arch-ontology',
                ],

                // PlatformStackDiagram (PlatformStack.js, index="02" label="The stack")
                // FIDELITY EXCEPTION: the platform-stack diagram component renders
                // its own headline ("The most intelligent infrastructure in home
                // services") and DATA/LOGIC/ACTIONS pillars internally. The
                // `diagram` layout's headline_left/right and body fields are
                // unused for platform-stack rendering; we still populate header
                // metadata so the section index/label render correctly.
                [
                    'acf_fc_layout' => 'diagram',
                    'settings' => [
                        'section_index' => '02',
                        'section_label' => 'The stack',
                        'theme_variant' => 'light',
                    ],
                    'diagram_key'    => 'platform-stack',
                    'caption'        => '',
                    'index'          => '02',
                    'label'          => 'The stack',
                    'header_label'   => 'The stack',
                    'headline_left'  => 'The most intelligent',
                    'headline_right' => 'infrastructure in home services',
                    'body'           => '',
                    'closing_line'   => '',
                ],

                // ArchHeadless (Architecture.js, index="03" label="Headless by design")
                [
                    'acf_fc_layout' => 'section_contrast',
                    'settings' => [
                        'section_index' => '03',
                        'section_label' => 'Headless by design',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'         => 'Headless by design',
                    'headline'        => 'The product is the infrastructure.',
                    'headline_accent' => 'The interface is your choice.',
                    'body_left'       => '<p>IgniteIQ is headless by design. In software, <strong>"headless"</strong> means a product where the underlying platform - the data, the logic, the actions - is decoupled from any single user interface. The platform runs underneath; the interface is interchangeable. It can be a dashboard, a spreadsheet, a Slack message, a custom app, or no human surface at all.</p>',
                    'body_right'      => "<p>The IgniteIQ infrastructure runs inside your cloud. The output can live in our studio if you want a place to start. It can flow into the tools your team already uses. It can power custom applications your developers build on top of it. And it can be acted on directly by AI agents and automations without any human surface at all. The platform doesn't depend on the head - and that's the point. The world's largest enterprise software companies are spending years rebuilding their products this way. We started here.</p>",
                    'table_header_old' => 'The old model',
                    'table_header_new' => 'The headless model',
                    'rows' => [
                        ['old_text' => 'A SaaS app you log into',          'new_text' => 'A platform that runs in your cloud'],
                        ['old_text' => 'Seats, licenses, and dashboards',  'new_text' => 'APIs, ontology, and agents'],
                        ['old_text' => 'One UI, take it or leave it',      'new_text' => 'Any interface — or no interface at all'],
                        ['old_text' => 'Your data lives in their product', 'new_text' => 'Your data lives in your warehouse'],
                        ['old_text' => 'You rent the interface',           'new_text' => 'You own the infrastructure'],
                    ],
                ],

                // ArchHowItDeploys (Architecture.js, index="04" label="How it deploys")
                [
                    'acf_fc_layout' => 'section_stack',
                    'settings' => [
                        'section_index' => '04',
                        'section_label' => 'How it deploys',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => 'From contract to live',
                    'headline' => 'Deployed Fast. Decades of Value.',
                    'headline_lead' => 'Deployed Fast.',
                    'headline_gap'  => 'Decades of Value.',
                    'body'     => 'Versioned. Auditable. Every deployment ships through the same defined process — and every framework update flows back to every operator on the platform.',
                    'layout'   => 'grid-3col',
                    'items' => [
                        ['step' => 'Day 0',   'title' => 'Workspace provisioned',    'body' => 'Snowflake/BigQuery account stood up under your credentials. Ontology framework v4.2 deployed as code.'],
                        ['step' => 'Day 1–2', 'title' => 'Source systems connected', 'body' => 'CRM, dispatch, accounting, marketing. 15–25 systems. Pipelines land raw data into your warehouse.'],
                        ['step' => 'Day 3–5', 'title' => 'Ontology activated',       'body' => 'Resolution layer maps source records into ontology entities. Identity stitching runs across systems.'],
                        ['step' => 'Day 6–7', 'title' => 'Live decisions',           'body' => 'Armory Intel queries the resolved ontology. Signal monitors it. Agents act on it. All inside your cloud.'],
                        ['step' => 'Ongoing', 'title' => 'Versioned, compounding',   'body' => 'Framework updates ship via git. New entities, edge cases, and patterns flow back from every deployment.'],
                    ],
                ],

                // ArchSecurity (Architecture.js, index="05" label="Security model")
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '05',
                        'section_label' => 'Security model',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => 'Security',
                    'headline' => 'Your data, safe. Secured. Governed.',
                    'headline_lead'  => 'Your data, safe.',
                    'headline_gap'   => 'Secured. Governed.',
                    'headline_align' => 'left',
                    'intro'      => '',
                    'columns'    => '3',
                    'style'      => 'grid-divided',
                    'hide_index' => true,
                    'items' => [
                        ['index_number' => '01', 'title' => 'Customer cloud isolation', 'body' => 'IgniteIQ never holds your data outside your account. There is no shared multi-tenant warehouse.'],
                        ['index_number' => '02', 'title' => 'Scoped credentials',       'body' => 'The framework reads under a least-privilege role. Read scope is documented and audited per-deployment.'],
                        ['index_number' => '03', 'title' => 'Audit logging',            'body' => 'Every framework query is logged in your warehouse, queryable by you, retained on your terms.'],
                        ['index_number' => '04', 'title' => 'Versioned framework',      'body' => 'You pin the framework version. Updates ship with release notes; you decide when to take them.'],
                        ['index_number' => '05', 'title' => 'SOC 2 in-flight',          'body' => 'Type II audit underway. Standard enterprise controls, security questionnaires, and DPA available.'],
                        ['index_number' => '06', 'title' => 'No PII training',          'body' => 'No customer data is used to train models outside the customer environment. Period.'],
                    ],
                    'scenarios' => [],
                ],

                // ArchCTA (Architecture.js)
                [
                    'acf_fc_layout' => 'cta_banner',
                    'settings' => [
                        'section_index' => '',
                        'section_label' => 'Ready to deploy',
                        'theme_variant' => 'dark',
                    ],
                    'eyebrow'       => 'Ready to deploy',
                    'headline'      => 'Get started today.',
                    'body'          => 'From contract to a live, queryable intelligence engine in seven days. We share security documentation, deployment plans, and a price up front.',
                    'primary_cta'   => ['label' => 'Start the conversation', 'url' => '/contact/'],
                    'secondary_cta' => ['label' => 'Back to overview',       'url' => '/'],
                    'variant'       => 'dark',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // ONTOLOGY — Ontology.js render order:
        // OntologyHero → WhatIsAnOntology (01) → WhyHomeServices (02)
        // → ArchStackDiagram (03 The flow) → CoreEntities (04)
        // → HowItDeploys (05) → CTASection
        // ───────────────────────────────────────────────────────────
        private static function page_ontology() {
            return [
                // OntologyHero (Ontology.js)
                [
                    'acf_fc_layout' => 'hero_ontology',
                    'eyebrow'       => '● The ontology · v4.2',
                    'headline_segments' => [
                        ['text' => 'Ontology is the',       'muted' => false],
                        ['text' => ' nouns and verbs ',     'muted' => true],
                        ['text' => 'of how your business',  'muted' => false],
                        ['text' => ' actually runs.',       'muted' => true],
                    ],
                    'lede' => "Every home services business runs on a web of interconnected relationships - jobs, customers, technicians, calls, invoices - moving and changing every hour. An ontology is the layer that encodes those relationships into a shared model: the ground truth of how your business is actually operating. It's the difference between a company that runs on assumptions scattered across fifteen systems, and a company that runs on a single, shared definition of itself.",
                ],

                // WhatIsAnOntology (Ontology.js, index="01" label="The concept")
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'The concept',
                        'theme_variant' => 'sunken',
                    ],
                    'eyebrow'  => '',
                    'headline' => 'Great decisions require three things. The ontology encodes all of them.',
                    'headline_lead'  => 'Great decisions require three things.',
                    'headline_gap'   => 'The ontology encodes all of them.',
                    'headline_align' => 'left',
                    'intro'    => '',
                    'columns'  => '3',
                    'style'    => 'bordered',
                    'items' => [
                        ['index_number' => '01', 'title' => 'Data.',    'eyebrow_label' => 'Data',    'body' => 'The current state of your business. Every job, customer, technician, invoice, and call from every system, resolved into a single set of objects with shared definitions.'],
                        ['index_number' => '02', 'title' => 'Logic.',   'eyebrow_label' => 'Logic',   'body' => 'How your business thinks about those objects. The rules — how margin is calculated, how a callback is defined, how a lead becomes a job — modeled so every tool and every agent applies them the same way.'],
                        ['index_number' => '03', 'title' => 'Actions.', 'eyebrow_label' => 'Actions', 'body' => 'What your business does about it. Dispatching, pricing, reordering, escalating. The ontology models the decisions a business takes so AI and humans can take those decisions inside the same system.'],
                    ],
                    'scenarios' => [],
                ],

                // WhyHomeServices (Ontology.js, index="02" label="Why home services")
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '02',
                        'section_label' => 'Why home services',
                        'theme_variant' => 'dark',
                    ],
                    'eyebrow'  => '',
                    'headline' => "Generic ontologies don't know what a callback costs you.",
                    'headline_lead'  => 'Generic ontologies',
                    'headline_gap'   => "don't know what a callback costs you.",
                    'headline_align' => 'left',
                    'intro'    => "The AI tools sold into home services promise to make your business smarter. They can't. The data underneath them is fragmented, contradictory, and shaped for someone else's industry. We didn't start from a blank slate - the IgniteIQ ontology was built next to operators, inside the trucks, on the dispatch board, in the books, and shaped specifically for the economics of HVAC, electrical, plumbing, and the rest of the trades.",
                    'columns'  => '3',
                    'style'    => 'bordered',
                    'items' => [
                        ['index_number' => '01', 'title' => 'The economics are unit-based.',         'body' => 'Margin per job. Cost per lead. Revenue per technician hour. The ontology is wired to the unit economics that actually matter — not adapted from retail or SaaS.'],
                        ['index_number' => '02', 'title' => 'The data is messy and operational.',    'body' => 'Field-typed by techs. Reconciled across CRM, dispatch, accounting. The ontology resolves naming, ID, and timing conflicts that would crash a generic model.'],
                        ['index_number' => '03', 'title' => 'The decisions repeat across operators.', 'body' => 'The 12 core entities — Job, Lead, Customer, Technician, Invoice, Equipment, Channel, Call, Dispatch, Payment, Inventory, Membership — are the operational DNA of every home services business.'],
                    ],
                    'scenarios' => [],
                ],

                // ArchStackDiagram on ontology page (Ontology.js: index="03", headerLabel="The flow")
                [
                    'acf_fc_layout' => 'diagram',
                    'settings' => [
                        'section_index' => '03',
                        'section_label' => 'The flow',
                        'theme_variant' => 'light',
                    ],
                    'diagram_key'    => 'stack',
                    'caption'        => '',
                    'index'          => '03',
                    'label'          => 'The flow',
                    'header_label'   => 'The flow',
                    'headline_left'  => 'One ontology.',
                    'headline_right' => 'Every product, every model, every system reads from it.',
                    'body'           => 'The IgniteIQ ontology resolves the operational entities of a home services business into a single shared object model. Analytics queries it. Agents act on it. Automations write back through it. Every flow in the diagram is a real edge in your warehouse.',
                    'closing_line'   => 'Your business makes a thousand decisions a day. We make every one faster, smarter, and right.',
                ],

                // CoreEntities — KPI strip (Ontology.js, index="04" label="The 12 core entities")
                [
                    'acf_fc_layout' => 'section_stats',
                    'settings' => [
                        'section_index' => '04',
                        'section_label' => 'The 12 core entities',
                        'theme_variant' => 'light',
                    ],
                    'headline'       => "A home services business isn't simple. The ontology makes it usable.",
                    'headline_lead'  => "A home services business isn't simple.",
                    'headline_gap'   => 'The ontology makes it usable.',
                    'headline_align' => 'left',
                    'intro'          => "A single mid-size operator runs on hundreds of distinct object types, thousands of relationships, and dozens of source systems that don't agree on what any of them mean. The IgniteIQ ontology resolves all of it — into a model that's queryable, governable, and built to compound as your business grows.",
                    'style'          => 'bare',
                    'stats' => [
                        ['value' => '200+',       'label' => 'Object types',   'footnote' => ''],
                        ['value' => '5,000+',     'label' => 'Relationships',  'footnote' => ''],
                        ['value' => '25+',        'label' => 'Source systems', 'footnote' => ''],
                        ['value' => 'Continuous', 'label' => 'Resolution',     'footnote' => ''],
                    ],
                ],

                // CoreEntities — supporting block trio (Ontology.js, same index 04)
                // FIDELITY EXCEPTION: the export renders the KPI strip and the
                // 3-column "One customer, not four / One job / One technician"
                // grid inside the same component. Splitting into two flexible-
                // content rows preserves the copy verbatim using the closest
                // schema layouts (section_stats + section_pillars). Section
                // index/label is left BLANK on this row so the visible marker
                // ("04 The 12 core entities") only renders once, on the
                // section_stats row above.
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '',
                        'section_label' => '',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'     => '',
                    'headline'    => '',
                    'intro'       => '',
                    'columns'     => '3',
                    'style'       => 'bordered',
                    'compact_top' => true,
                    'items' => [
                        ['index_number' => '01', 'title' => 'One customer, not four.',           'body' => 'The same homeowner might exist as four different records across CRM, dispatch, billing, and call tracking. The ontology resolves them into a single customer object — with one history, one value, one definition.'],
                        ['index_number' => '02', 'title' => 'One job, across every system.',     'body' => 'A "job" in ServiceTitan is not the same as a "job" in your accounting system or your marketing platform. The ontology reconciles every source into one job object, so every tool downstream — analytics, dispatch, AI agents — speaks the same language.'],
                        ['index_number' => '03', 'title' => 'One technician, with full context.', 'body' => 'Dispatch knows their schedule. Payroll knows their hours. Certifications live somewhere else entirely. The ontology rolls them into one technician object that every part of the business can query.'],
                    ],
                    'scenarios_layout' => 'pullquote',
                    'scenarios' => [
                        [
                            'tag'  => 'The point',
                            'body' => 'What looks like fifteen disconnected systems on the surface is one business underneath. The ontology is the layer where it finally acts like one.',
                        ],
                    ],
                ],

                // HowItDeploys (Ontology.js, index="05" label="How it deploys")
                [
                    'acf_fc_layout' => 'section_stats',
                    'settings' => [
                        'section_index' => '05',
                        'section_label' => 'How it deploys',
                        'theme_variant' => 'sunken',
                    ],
                    'headline'       => 'The ontology ships into your warehouse as code.',
                    'headline_lead'  => 'The ontology ships into',
                    'headline_gap'   => 'your warehouse as code.',
                    'headline_align' => 'left',
                    'intro'          => 'The IgniteIQ ontology deploys directly into your cloud account — Snowflake, BigQuery, or Databricks — and resolves your operational data in seven days. Every framework update flows back to every operator on the platform, so the ontology gets sharper every month without a re-implementation. Versioned. Auditable. In your warehouse, not ours.',
                    'style'          => 'bare',
                    'stats' => [
                        ['value' => 'Versioned code', 'label' => 'Ships as',     'footnote' => ''],
                        ['value' => 'Your warehouse', 'label' => 'Lives in',     'footnote' => ''],
                        ['value' => '< 7 days',       'label' => 'Time to live', 'footnote' => ''],
                        ['value' => 'Continuous',     'label' => 'Updates',      'footnote' => ''],
                    ],
                ],

                // CTASection (SectionsB.js, shared with home)
                [
                    'acf_fc_layout' => 'cta_banner',
                    'settings' => [
                        'section_index' => '',
                        'section_label' => 'Get started today',
                        'theme_variant' => 'dark',
                    ],
                    'eyebrow'       => 'Get started today',
                    'headline'      => 'Own Your Intelligence.',
                    'body'          => 'Your cloud. Your data. Your Intelligence. Deployed in days, not months - and yours from the moment we ship.',
                    'primary_cta'   => ['label' => 'Get started today', 'url' => '/contact/'],
                    'secondary_cta' => ['label' => 'How it works',      'url' => '/how-it-works/'],
                    'variant'       => 'dark',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // COMPANY — Company.js render order:
        // CompanyHero → WhatWeAre (01) → FoundingStory (02)
        // → MissionPrinciples (03) → TeamDeep (03 The team) → ContactSection
        // ───────────────────────────────────────────────────────────
        private static function page_company() {
            return [
                // CompanyHero (Company.js)
                [
                    'acf_fc_layout' => 'hero_editorial',
                    // Template (heroes/editorial.php:38) renders its own bullet.
                    'eyebrow'        => 'Company',
                    // FIDELITY: Company.js hero (lines 24-40) renders a single
                    // H1 at clamp(44,5.2vw,88px) with the second sentence in
                    // a tertiary inline span. The `inline-2tone` size_variant
                    // + per-line `muted` flags reproduce that exactly.
                    'size_variant'   => 'inline-2tone',
                    'headline_lines' => [
                        ['line' => 'Operators who ran the trucks.', 'muted' => false],
                        ['line' => 'Architects who modernized the industry. Innovators building what’s next.', 'muted' => true],
                    ],
                    'body'          => '',
                    'primary_cta'   => ['label' => '', 'url' => ''],
                    'secondary_cta' => ['label' => '', 'url' => ''],
                    'dark'          => false,
                ],

                // TeamDeep (Company.jsx 2-6, index="01" label="The team").
                // Render order moved up to immediately follow the hero.
                [
                    'acf_fc_layout' => 'section_team',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'The team',
                        'theme_variant' => 'light',
                    ],
                    // Two-column header: stacked headline left, lede right.
                    'headline'       => 'Operators. Innovators. Architects.',
                    'headline_lead'  => 'Operators.<br>Innovators.',
                    'headline_gap'   => 'Architects.',
                    'lede'           => 'The team behind IgniteIQ is back at it to revolutionize home services in the AI economy. Owners, operators, and the architects who built the billion-dollar platform the industry runs on today, are building for a new future that will change the home services landscape forever.',
                    'featured_count' => 3,
                    'avatar_style'   => 'square',
                    // Card order per Matt's 2026-06-04 request:
                    // Darren, Ryan, Scott (founder row), then Josh, Matt.
                    'members' => [
                        // Darren Merritt restored to the public team grid per
                        // Matt's 2026-06-04 decision (supersedes the
                        // 2026-04-29 removal). Export 2-6 lists him as a
                        // founder.
                        [
                            'name'       => 'Darren Merritt',
                            'role'       => 'VP of Engineering',
                            'credential' => 'Former VP of Engineering at ServiceTitan.',
                            'bio'        => 'Built and scaled the engineering behind the billion-dollar platform, then served as CPO at PipeDreams, a PE-backed home services roll-up. Brings the hard-won perspective on what breaks at scale and how to engineer past it.',
                            'photo'      => false,
                            'photo_slug' => 'darren',
                        ],
                        [
                            'name'       => 'Ryan Sciandri',
                            'role'       => 'CTO',
                            'credential' => 'Former Chief Data Architect at ServiceTitan.',
                            'bio'        => 'Led data architecture across every department of the platform that digitized the industry. Knows home services data at the schema level better than almost anyone alive — and knows exactly where the seams are.',
                            'photo'      => false,
                            'photo_slug' => 'ryan',
                        ],
                        [
                            'name'       => 'Scott Rayden',
                            'role'       => 'Founder & CEO',
                            'credential' => 'Multi-exit entrepreneur and home services owner.',
                            'bio'        => 'Built and exited an electrical / HVAC company, ran the largest independent customer-acquisition agency in the U.S. 350 people, $2B/yr in managed media, six M&A transactions, $150M+ in exit value. He has lived the operator side of the problem and is obsessed with bringing innovation and intelligence to the trades.',
                            'photo'      => false,
                            'photo_slug' => 'scott',
                        ],
                        [
                            'name'       => 'Josh Scott',
                            'role'       => 'Go To Market',
                            'credential' => 'Turns a signed contract into a live intelligence engine in days.',
                            'bio'        => 'Owns the deployment motion and the customer experience from kickoff through compounding production use. The reason go-live is measured in days, not months.',
                            'photo'      => false,
                            'photo_slug' => 'josh',
                        ],
                        [
                            'name'       => 'Matt Lawler',
                            'role'       => 'Marketing',
                            'credential' => 'Builds the product surface operators actually live in.',
                            'bio'        => 'Shapes the Armory dashboards, the agent workflows, and the ontology authoring tools — translating how operators run their business into an interface that runs on top of the framework.',
                            'photo'      => false,
                            'photo_slug' => 'matt',
                        ],
                    ],
                ],

                // WhatWeAre (Company.jsx 2-6, index="02" label="We’re not an AI company")
                [
                    'acf_fc_layout' => 'section_split',
                    'settings' => [
                        'section_index' => '02',
                        'section_label' => 'We’re not an AI company',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => '',
                    'headline' => 'We’re not an AI company. We are the foundation AI runs on.',
                    'headline_lead' => 'We’re not an AI',
                    'headline_gap'  => 'company. We are the foundation AI runs on.',
                    // FIDELITY: lowercase "allowing" + the trailing fragment
                    // are verbatim from Company.jsx 2-6 (line 63). Source copy
                    // reads slightly rough; ported byte-accurate per contract.
                    'body'     => '<p>We’re not here to sell you a new flashy tool. We build the infrastructure that makes your systems, tools, agents, automations, and AI actually work. allowing you to build for the future.</p>',
                    'media_slot'  => 'diagram',
                    'diagram_key' => '',
                    'reverse'  => false,
                ],

                // FoundingStory (Company.jsx 2-6, index="03" label="The founding story")
                [
                    'acf_fc_layout' => 'section_prose',
                    'settings' => [
                        'section_index' => '03',
                        'section_label' => 'The founding story',
                        'theme_variant' => 'sunken',
                    ],
                    'eyebrow'  => '',
                    'headline' => "We've been on every side of this problem.",
                    'style'    => 'split',
                    'paragraphs' => [
                        ['paragraph' => "<p>Our founder ran an electrical and HVAC company. He built and exited the largest independent customer acquisition agency in the trades. He's sat in the truck, on the dispatch board, and across the table from operators trying to make sense of seven systems that don't talk to each other.</p>"],
                        ['paragraph' => '<p>Two of our senior engineering leaders helped build ServiceTitan from the inside — one as Executive Architect across every API, one as VP of Engineering. They know home services data at the schema level better than almost anyone in the industry.</p>'],
                        ['paragraph' => "<p>We started IgniteIQ because the answer wasn't another app or another dashboard. The answer was a structured model of the business — owned by the operator, deployed in their cloud, and built specifically for the trades. That's the foundation. Everything we ship sits on top of it.</p>"],
                        ['paragraph' => '<p><em>● Founded 2024 · Headquartered remote-first</em></p>'],
                    ],
                ],

                // MissionPrinciples (Company.jsx 2-6, index="04" label="What we believe")
                [
                    'acf_fc_layout' => 'section_pillars',
                    'settings' => [
                        'section_index' => '04',
                        'section_label' => 'What we believe',
                        'theme_variant' => 'light',
                    ],
                    'eyebrow'  => '',
                    'headline' => 'Three things we believe about the next decade of home services.',
                    'headline_lead'  => 'Three things we believe about',
                    'headline_gap'   => 'the next decade of home services.',
                    'headline_align' => 'left',
                    'intro'    => '',
                    'columns'  => '3',
                    'style'    => 'top-border',
                    'items' => [
                        ['index_number' => '01', 'title' => 'Your data is yours.',             'body' => 'It lives in your cloud. The vendor that won’t agree to that is renting you back your own business.'],
                        ['index_number' => '02', 'title' => 'Intelligence compounds.',         'body' => 'Every week the system gets sharper. Every decision gets faster. The operators who build on solid foundations outlast every cycle of new tools.'],
                        ['index_number' => '03', 'title' => 'Infrastructure beats interface.', 'body' => 'Dashboards are wallpaper. Foundations are leverage. The companies that win this decade will be the ones that built the foundation, not the ones that licensed the dashboard, a workflow, or an automation.'],
                    ],
                    'scenarios' => [],
                ],

                // ContactSection (Company.js — same copy as ArchCTA)
                [
                    'acf_fc_layout' => 'cta_banner',
                    'settings' => [
                        'section_index' => '',
                        'section_label' => 'Ready to deploy',
                        'theme_variant' => 'dark',
                    ],
                    'eyebrow'       => 'Ready to deploy',
                    'headline'      => 'Get started today.',
                    'body'          => 'From contract to a live, queryable intelligence engine in seven days. We share security documentation, deployment plans, and a price up front.',
                    'primary_cta'   => ['label' => 'Start the conversation', 'url' => '/contact/'],
                    'secondary_cta' => ['label' => 'Back to overview',       'url' => '/'],
                    'variant'       => 'dark',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // CONTACT — Contact.js render order:
        // ContactHero → ContactBody (form + offices)
        // ───────────────────────────────────────────────────────────
        private static function page_contact() {
            return [
                // ContactHero (Contact.js)
                [
                    'acf_fc_layout' => 'hero_minimal',
                    // Template (heroes/minimal.php:20) renders its own bullet
                    // dot span; seed must NOT prefix `●` or it doubles.
                    'eyebrow'        => 'Contact',
                    'headline_lines' => [
                        ['line' => 'Let’s talk.'],
                        ['line' => 'About your data, your decisions, and what compounds when both are yours.', 'muted' => true],
                    ],
                    'body' => '<p>Tell us a bit about your business. We’ll come back to you within one business day with the right person on our side of the table.</p>',
                ],

                // ContactBody — form + offices side panel
                // FIDELITY EXCEPTION: the export's left column lists offices
                // (San Diego HQ — 1234 Kettner Blvd, Suite 500, San Diego, CA
                // 92101; San Francisco — 600 Montgomery St, 12th Floor, San
                // Francisco, CA 94111) plus hello@igniteiq.com / +1 (619)
                // 555-0114. The `form` layout has no fields for office data,
                // so this content is omitted from the seed and lives in the
                // Contact form template / Site Settings instead.
                [
                    'acf_fc_layout' => 'form',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'Send us a note',
                        'theme_variant' => 'light',
                    ],
                    'form_type' => 'contact',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // SIGN IN — SignIn.js
        // ───────────────────────────────────────────────────────────
        private static function page_signin() {
            return [
                [
                    'acf_fc_layout' => 'hero_minimal',
                    // Template (heroes/minimal.php:20) renders its own bullet.
                    'eyebrow'        => 'Customer access',
                    'headline_lines' => [
                        ['line' => 'Welcome back.'],
                        ['line' => 'Your intelligence is waiting.', 'muted' => true],
                    ],
                    'body' => '<p>Sign in to your IgniteIQ workspace to query the ontology, monitor signals, and run agents across your business.</p>',
                ],
                [
                    'acf_fc_layout' => 'form',
                    'settings' => [
                        'section_index' => '01',
                        'section_label' => 'Sign in',
                        'theme_variant' => 'light',
                    ],
                    'form_type' => 'signin',
                ],
            ];
        }

        // ───────────────────────────────────────────────────────────
        // RIDE ALONG — Demo.jsx port (Wave V-1 follow-up).
        // Source: exports/latest/demo.html + js/Demo.jsx (650 lines).
        // The page is rendered by page-ride-along.php which calls the
        // self-contained section partial
        // template-parts/sections/ride-along-page.php — bypassing the
        // ACF flexible-content pipeline. Returning an empty array here
        // leaves page_sections blank in WP admin while still letting
        // find_or_create_page seed the page itself.
        //
        // The page is created with post_status=draft (see
        // default_post_status_for_slug). Nav auto-appends "Ride along"
        // once the page is published (see template-parts/nav.php).
        // ───────────────────────────────────────────────────────────
        private static function page_ride_along() {
            return [];
        }
    }
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    WP_CLI::add_command('igniteiq', 'IgniteIQ_CLI');
}
