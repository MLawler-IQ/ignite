# IgniteIQ Design System

**Version:** v4.2 · **Source:** `exports/latest/` (canonical) · **Audience:** Claude Design (paste-in brand reference)

This document captures the complete visual language of the IgniteIQ marketing site (igniteiq.com). Every value is quoted verbatim from the canonical export — `css/tokens.css`, `css/responsive.css`, and the lifted React components in `js/`. Where the spec referenced files that don't exist (`BoundaryDiagram.js`, `OperatorStackList.js`), it's noted explicitly.

---

## 1. Brand voice

### Mission

> Owning your intelligence is the only advantage that compounds.

### Tagline

> Own Your Intelligence.

### Positioning

Intelligence infrastructure for the modern trades (home services). Not an agency. Not an AI vendor. A framework that ships into the customer's own cloud, unifies their operational data into a shared ontology, and runs AI products on top of it. **Customer data stays put. The framework gets smarter.**

### Voice rules

- Bold, direct, ownership-focused.
- Short declarative sentences. Active voice.
- No filler, no jargon, no hedging.
- "Own" is the power word.
- Two-tone headlines: declarative primary clause + softer (tertiary-color) reflective clause. Often a period or em-dash separates them.

### Language — say / don't say

| Say | Don't say |
| --- | --- |
| systems, infrastructure, architecture | services, deliverables |
| we build, we architect | we manage, retainer |
| investment, intelligence partner | pricing, cost, vendor |
| ontology, framework, the stack | platform, suite, solution |
| compounds, ships, deploys | rolls out, executes, performs |
| operators | clients, users |

### Headline gallery (verbatim, capturing the voice)

- "Own Your Intelligence."
- "AI without the foundation is expensive guessing."
- "What used to take a year. Shipped in a week."
- "Better decisions. *What changes for you?*"
- "Customer data stays put. The framework gets smarter."
- "Built on your cloud. Run on your data."
- "Ontology is the nouns and verbs of how your business actually runs."
- "The product is the infrastructure. The interface is your choice."

---

## 2. Color system

All values are oklch (CSS color level 4). Stored as CSS custom properties in `css/tokens.css`. The system has one neutral ramp (`--ink-*`), one accent ramp (`--ignite-*`), four semantic state tokens, and theme-mapped role tokens that flip between light and dark.

### 2.1 Ink ramp (neutrals — 14 stops)

| Token | oklch | Role |
| --- | --- | --- |
| `--ink-0` | `oklch(100% 0 0)` | pure white |
| `--ink-50` | `oklch(98.6% 0.001 286)` | page bg (light) |
| `--ink-100` | `oklch(96.4% 0.002 286)` | sunken surface |
| `--ink-150` | `oklch(93.2% 0.003 286)` | hairline / divider |
| `--ink-200` | `oklch(88.0% 0.004 286)` | border default |
| `--ink-300` | `oklch(78.0% 0.005 286)` | muted line, scrollbar |
| `--ink-400` | `oklch(64.0% 0.006 286)` | placeholder, fg-tertiary |
| `--ink-500` | `oklch(50.0% 0.007 286)` | fg-secondary, icons |
| `--ink-600` | `oklch(38.0% 0.007 286)` | body alt |
| `--ink-700` | `oklch(27.5% 0.006 286)` | heading alt |
| `--ink-800` | `oklch(18.0% 0.005 286)` | near-black surface |
| `--ink-900` | `oklch(12.0% 0.004 286)` | deep surface |
| `--ink-950` | `oklch(7.5% 0.003 286)` | canvas dark |
| `--ink-1000` | `oklch(0% 0 0)` | true black (logo, CTA band) |

### 2.2 Ignite (accent red — 9 stops)

| Token | oklch | Role |
| --- | --- | --- |
| `--ignite-50` | `oklch(97% 0.018 25)` | tint wash |
| `--ignite-100` | `oklch(93% 0.045 25)` | accent pill bg (`#FBE0DD` equiv) |
| `--ignite-200` | `oklch(85% 0.10 25)` | — |
| `--ignite-300` | `oklch(75% 0.16 25)` | — |
| `--ignite-400` | `oklch(65% 0.21 25)` | dark-mode accent (eyebrow on dark) |
| `--ignite-500` | `oklch(57.5% 0.232 25)` | **primary brand red** |
| `--ignite-600` | `oklch(50% 0.215 25)` | hover state |
| `--ignite-700` | `oklch(42% 0.18 25)` | — |
| `--ignite-800` | `oklch(33% 0.13 25)` | — |
| `--ignite-900` | `oklch(24% 0.085 25)` | — |

`--ignite-500` (also expressible as `rgb(239, 68, 68)` / `#E11D2E` in some diagrams) is used for: eyebrow bullet, primary CTA buttons, day timeline markers, required-field asterisks, focus underline on form inputs, focus ring, link hover decoration, accent stroke on the ontology layer in `FrameworkDiagram`, the StackDiagram base plane gradient, and the bottom-of-CTA red glow shadow.

### 2.3 Semantic state palette

```
--state-success: oklch(62% 0.13 155);
--state-warning: oklch(76% 0.13 75);
--state-info:    oklch(60% 0.10 240);
--link-blue:     oklch(70% 0.13 238);
--state-danger:  var(--ignite-500);
```

### 2.4 Role tokens — light theme (default)

```
--bg-canvas:      var(--ink-50);
--bg-surface:     var(--ink-0);
--bg-raised:      var(--ink-0);
--bg-sunken:      var(--ink-100);
--bg-inverse:     var(--ink-1000);

--fg-primary:     var(--ink-1000);
--fg-secondary:   var(--ink-600);
--fg-tertiary:    var(--ink-500);
--fg-muted:       var(--ink-400);
--fg-on-inverse:  var(--ink-50);
--fg-accent:      var(--ignite-500);

--border-subtle:  var(--ink-150);
--border-default: var(--ink-200);
--border-strong:  var(--ink-300);
--border-inverse: var(--ink-800);

--focus-ring:     var(--ignite-500);
--selection-bg:   oklch(57.5% 0.232 25 / 0.18);
```

### 2.5 Role tokens — dark theme override (`:root[data-theme="dark"], .iiq-dark`)

```
--bg-canvas:      var(--ink-950);
--bg-surface:     var(--ink-900);
--bg-raised:      var(--ink-800);
--bg-sunken:      var(--ink-1000);
--bg-inverse:     var(--ink-0);

--fg-primary:     var(--ink-50);
--fg-secondary:   var(--ink-300);
--fg-tertiary:    var(--ink-400);
--fg-muted:       var(--ink-500);
--fg-on-inverse:  var(--ink-1000);
--fg-accent:      var(--ignite-400);

--border-subtle:  oklch(20% 0.005 286);
--border-default: oklch(26% 0.005 286);
--border-strong:  oklch(34% 0.006 286);
--border-inverse: var(--ink-200);
```

### 2.6 Where each accent token appears

| Token | Used for |
| --- | --- |
| `--ignite-500` | red bullet on eyebrows, primary CTA buttons, day-timeline markers, required asterisk, focus underline, focus ring, link hover decoration, accent border on the ontology layer in `FrameworkDiagram`, "● V4.2" status pill in scenes |
| `--ignite-400` | eyebrow color when on dark backgrounds (e.g. CTA banner, ArchHero, "Customer access" eyebrow on SignIn dark sidebar) |
| `oklch(57.5% 0.232 25 / 0.18)` | radial red glow on dark heroes (Cinematic, ArchHero, SignIn aside), text selection background |
| `oklch(57.5% 0.232 25 / 0.4)` | red radial glow on team-member avatars; primary CTA shadow ring |
| `oklch(57.5% 0.232 25 / 0.15)` | day-marker pulse halo (timeline) |

---

## 3. Typography

### 3.1 Font families

```
--font-display: 'Aeonik', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
--font-sans:    'Aeonik', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
--font-mono:    'Aeonik Fono', ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
```

`Aeonik` weights: 300 Light, 400 Regular, 500 Medium (mapped 500–600), 700 Bold.
`Aeonik Fono` weights: 300 Light, 400 Medium (mapped 400–600). All `font-display: swap`.

### 3.2 Type scale

```
--text-2xs:  11px;   /* eyebrow */
--text-xs:   12px;
--text-sm:   13px;   /* footer link */
--text-base: 15px;
--text-md:   16px;   /* body default */
--text-lg:   18px;   /* intro / lead */
--text-xl:   22px;   /* h4, lead */
--text-2xl:  28px;
--text-3xl:  36px;
--text-4xl:  48px;
--text-5xl:  64px;
--text-6xl:  88px;
--text-7xl:  120px;
```

### 3.3 Line-height, tracking, weight tokens

```
--leading-tight: 1.05;   --leading-snug: 1.2;   --leading-normal: 1.45;   --leading-loose: 1.65;

--tracking-tighter: -0.04em;   --tracking-tight: -0.02em;   --tracking-normal: 0;
--tracking-wide:    0.04em;    --tracking-eyebrow: 0.14em;

--weight-regular: 400;   --weight-medium: 500;   --weight-semibold: 600;
--weight-bold:    700;   --weight-black:    800;
```

### 3.4 Heading defaults

| Element | Family | Size | Line-height | Tracking | Weight |
| --- | --- | --- | --- | --- | --- |
| h1 / .h1 | display | 64px | 1.05 | -0.04em | 600 |
| h2 / .h2 | display | 48px | 1.05 | -0.02em | 600 |
| h3 / .h3 | display | 28px | 1.2 | -0.02em | 600 |
| h4 / .h4 | sans | 22px | 1.2 | -0.02em | 600 |
| h5 / .h5 | sans | 18px | 1.2 | normal | 600 |
| h6 / .eyebrow | mono | 12px | 1.2 | 0.14em | 500, **uppercase**, color `--fg-tertiary` |
| p / .body | sans | 16px | 1.45 | normal | 400, color `--fg-secondary` |
| .lead | sans | 22px | 1.2 | -0.02em | 400, color `--fg-secondary` |

Headings always inherit `text-wrap: balance` when used at hero scale. Body prose uses `text-wrap: pretty`.

### 3.5 The eyebrow pattern

Mono, 11px, `letter-spacing: 0.18em`, uppercase, optionally prefixed with a 6×6px `var(--ignite-500)` square that pulses (`animation: iiqPulse 2s ease-in-out infinite`, opacity 1 → 0.35 → 1).

```
●  THE CONCEPT
```

When the eyebrow is paired with a section index, the pattern is two mono spans separated by a 16px gap:

```
01    THE CONCEPT
```

The bullet form is used for hero eyebrows ("● Contact", "● Company", "● The ontology · v4.2", "● Customer access"). The numbered form is used for `SectionFrame`-wrapped sections.

### 3.6 Two-tone headline pattern

The signature headline rhythm: a primary declarative clause in `--fg-primary`, followed by a softer reflective clause in `--fg-tertiary` (or a muted oklch on dark heroes). Renders inside a single `<h1>`/`<h2>` as adjacent `<span>`s.

Examples (verbatim from source):

- "Better decisions. **[What changes for you?]**" — `js/SectionsA.js`
- "Invest in outcomes. **[Not experiments.]**" — `js/InvestInOutcomes.js`
- "What used to take a year. **[Shipped in a week.]**" — `js/SectionsA.js`
- "Every home services business is a decision-making engine. **[The data underneath is blurry.]**" — `js/SectionsA.js`
- "Built on your cloud. Run **[on your data.]**" — `js/Architecture.js` (the second-clause segments are " your cloud. " and " your data.")
- "Ontology is the **[nouns and verbs]** of how your business **[actually runs.]**" — `js/Ontology.js` (4-segment alternating)

Inline JSX shape:

```jsx
<h2 style={{ fontFamily: 'var(--font-display)', fontWeight: 600,
             letterSpacing: '-0.04em', lineHeight: 1.0,
             color: 'var(--fg-primary)' }}>
  Better decisions.{' '}
  <span style={{ color: 'var(--fg-tertiary)' }}>What changes for you?</span>
</h2>
```

### 3.7 Hero clamp values (verbatim)

| Hero variant | Clamp | Weight | Line-height | Tracking |
| --- | --- | --- | --- | --- |
| HeroEditorial (short ≤80ch) | `clamp(64px, 11vw, 184px)` | 700 | 0.92 | -0.04em |
| HeroEditorial (long >80ch) | `clamp(36px, 4.6vw, 72px)` | 600 | 1.04 | -0.04em |
| HeroCinematic (short) | `clamp(60px, 11vw, 188px)` | 700 | 0.92 | -0.04em |
| HeroCinematic (long) | `clamp(36px, 4.6vw, 72px)` | 600 | 1.04 | -0.04em |
| HeroSplit | `clamp(56px, 8vw, 132px)` | 700 | 0.92 | -0.05em |
| HeroStatement | `140px` (literal, balanced) | 600 | 0.95 | -0.045em, color `rgb(239,68,68)` |
| ArchHero (how-it-works) | `clamp(56px, 8.4vw, 144px)` | 600 | 0.94 | -0.05em |
| OntologyHero | `clamp(56px, 7.6vw, 132px)` | 600 | 0.92 | -0.05em |
| CompanyHero | `clamp(44px, 5.2vw, 88px)` | 600 | 1.06 | -0.04em |
| ContactHero | `clamp(56px, 7.6vw, 132px)` | 600 | 0.94 | -0.05em |
| SignIn aside h1 | `clamp(40px, 4.4vw, 64px)` | 600 | 1.02 | -0.04em |
| Section H2 (centered) | `clamp(40px, 4.8–5.6vw, 76px)` | 600 | 0.98–1.04 | -0.035em / -0.04em |
| Big section H2 (Invest, Deploy) | `clamp(48px, 6.4vw, 96px)` | 600 | 0.96 | -0.045em |
| CTA banner h2 | `clamp(56px, 7.6vw, 132px)` | 600 | 0.92 | -0.05em |

Body intro paragraphs sit at 18–22px / line-height 1.5–1.65 / `--fg-secondary`. Body prose is 14–16px.

### 3.8 Mobile type clamps (≤640px overrides)

```
.iiq-display-xl: clamp(40px, 11vw, 56px) !important;
.iiq-display-lg: clamp(34px, 9.5vw, 48px) !important;
.iiq-display-md: clamp(28px,   8vw, 38px) !important;
```

---

## 4. Layout & spacing

### 4.1 Spacing scale (4px base)

```
0,4,8,12,16,20,24,32,40,48,64,80,96,128
(--space-0 ... --space-32)
```

### 4.2 Border radii

```
--radius-none: 0;   --radius-xs: 2px;   --radius-sm: 4px;   --radius-md: 6px;
--radius-lg:   10px; --radius-xl: 16px; --radius-pill: 999px;
```

Buttons: `radius-md` (6px). Cards: `radius-lg` (10px). Form submit: `radius-sm` (4px). Inputs are flat (no radius — underline only).

### 4.3 Section padding

| Breakpoint | `iiq-section-pad` | `iiq-hero-pad` |
| --- | --- | --- |
| Desktop (>1024px) | `120px 32px 140px` | `96px 32px` (Editorial) / `120px 32px 0` (Cinematic) / `180px 32px 140px` (ArchHero) |
| Tablet (≤1024px) | `96px 24px 112px` | `140px 24px 96px` |
| Phone (≤640px) | `80px 20px 88px` | `120px 20px 72px` |

Custom CTA-band padding: `160px 32px` (no responsive shrink defined — uses default outer container).

### 4.4 Container max-widths

| Width | Used for |
| --- | --- |
| 1040 | Why It Matters centered prose section |
| 1100 | What Changes / Deployment headline + intro container |
| 1180 | (rare; secondary content widths) |
| 1200 | Invest In Outcomes headline container |
| 1240 | PlatformStackDiagram outer; Architecture mid-width content |
| 1320 | `SectionFrame` default content container (Reveal.js) |
| 1440 | Nav, hero outer, footer outer (page-edge bound) |

### 4.5 Grid systems

```
1.1fr / 0.9fr   — Problem section (text left, card right), gap 80
1.05fr / 0.95fr — HeroSplit (text left, image right bleeds 8% past column), gap 64
1fr / 1.6fr     — split-with-body sections (e.g. ArchFrameworkSide)
120px / 1fr / 1.4fr — Ontology lifecycle 3-col phase row
1fr / 1fr       — Problem body sub-grid (2 stacked paragraphs), gap 32
repeat(3, 1fr)  — Pillar grids (What Changes, Invest, Why Home Services), gap 64
repeat(4, 1fr)  — KPI strips, deployment timeline, core-entities stat strip, gap 24/32
1.5fr / 1fr / 1fr / 1fr / 1fr — Footer 5-col, gap 48
```

Mobile (`≤1024px`): 2-col → 1-col; 3-col → 2-col; 4-col → 2-col. (`≤640px`): everything → 1-col except `iiq-grid-4` which stays 2-col.

### 4.6 Card padding patterns

| Pattern | Used by |
| --- | --- |
| `32px 28px` | Operator-stack card (Problem), team avatar cards, security cells |
| `40px 32px` | Larger composite cards (rare) |
| `24px 24px 28px` | Deployment day cards (top-left dot pad accounts for the marker) |
| `20px 22px` | Hero KPI mini-cards (ArchHero stats) |
| `16px 12px` (chips) | Chip tags inside operator-stack card |
| `14px 22px` | Diagram chrome bars (e.g. ontology scene "YOUR DATA WAREHOUSE" header) |

### 4.7 Tone segmentation — Ontology hero (the 4-segment alternating pattern)

The OntologyHero h1 is composed as four spans alternating between `--fg-primary` and `--fg-tertiary`:

```jsx
<h1>
  Ontology is the                     {/* fg-primary */}
  <span> nouns and verbs </span>      {/* fg-tertiary */}
  of how your business                {/* fg-primary */}
  <span> actually runs.</span>        {/* fg-tertiary */}
</h1>
```

This is the only headline that uses 4 segments. Other two-tone headlines use exactly 2 segments.

---

## 5. Components — visual language

### 5.1 Eyebrow

```
┌──────────────────────────────┐
│  ●  THE CONCEPT              │   ← font-mono 11px / 0.18em / uppercase
└──────────────────────────────┘    bullet 6×6 var(--ignite-500), iiqPulse 2s
```

```jsx
<span style={{ display: 'inline-flex', alignItems: 'center', gap: 8,
               fontFamily: 'var(--font-mono)', fontSize: 11,
               letterSpacing: '0.18em', textTransform: 'uppercase',
               color: 'var(--ignite-500)' }}>
  <span style={{ width: 6, height: 6, background: 'var(--ignite-500)',
                 animation: 'iiqPulse 2s ease-in-out infinite' }} />
  THE CONCEPT
</span>
```

### 5.2 Section marker (`SectionFrame`)

Positioned absolute, `top: -120, left: 0`, padding `20px 0`. Two mono spans separated by a 16px gap.

```
01    THE CONCEPT
─────────────────────────────────────────────
                      (section content)
```

Section frame container: `padding: 120px 32px 140px`, `max-width: 1320`, optional `dark` mode flips bg to `--ink-1000` and text to `--ink-50` with `border-top: 1px solid oklch(20% 0.005 286)`.

### 5.3 Hero variants

#### HeroStatement (manifesto opener)
- Single literal-140px declarative sentence in `rgb(239, 68, 68)`, weight 600, line-height 0.95, tracking -0.045em, `text-wrap: balance`.
- Background: 72×72px hairline grid (`var(--border-subtle)`), opacity 0.32, masked by radial gradient `radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%)`.
- Parallax: grid `scrollY * 0.35`, corner chrome `* 0.25`, KPI strip `* 0.12`.
- 4-corner 14×14px chrome marks, `border-bottom: 1px solid var(--border-default)`.
- 4-col KPI strip below: gap 24, `margin-top: 88, padding-top: 28, border-top: 1px solid var(--border-default)`. Eyebrow labels mono 10px / 0.18em / `--fg-tertiary`. Values clamp(28px, 3.2vw, 40px) / weight 600 / -0.02em.

#### HeroEditorial (light, restrained)
- `min-height: calc(100vh - 64px)`, padding `96px 32px`, max-width 1440.
- Multi-line headline rendered as `<span style={{display:'block'}}>` per `\n` line. Earlier lines `--fg-tertiary`, final line `--fg-primary`.
- Subhead: 19px / 1.55 / max-width 640 / `--fg-secondary`, margin `32px 0 0`.
- 2 inline buttons: 14px / 500 / `14px 24px` / radius 6. Primary `var(--ignite-500)`, secondary transparent + 1px border.

#### HeroCinematic (dark, atmospheric)
- `min-height: 100vh`, padding `120px 32px 0`, `background: var(--ink-1000)`, color `var(--ink-50)`.
- LatticeCanvas overlay: 46-node ambient ontology lattice, drifting velocities `(rand-0.5)*0.18`, max-connection distance `min(180, max(120, min(w,h)*0.18))`, line stroke `rgba(255,255,255,α*0.28)`. 10% of nodes are red glow nodes (radial gradient `oklch(57.5% 0.232 25 / 0.5*pulse)` → transparent at 14px). Pulse: `0.6 + sin(t*0.018+seed)*0.4` (period ~6.3s). Respects `prefers-reduced-motion`.
- Glow layer: `radial-gradient(ellipse at 70% 30%, oklch(57.5% 0.232 25 / 0.18), transparent 50%), linear-gradient(to bottom, oklch(7.5% 0.003 286 / 0.2), oklch(7.5% 0.003 286 / 0.85) 90%)`.
- 4 corner dividers, 18×18px, `1px solid oklch(50% 0.005 286)`.
- Earlier headline lines `oklch(60% 0.005 286)`; final line `--ink-50`.
- KPI strip: 4-col, gap 32, margin-top 96, padding-top 28, top-border `1px solid oklch(22% 0.005 286)`. Value 32px / 600 / -0.025em.
- Primary button shadow: `0 0 0 1px oklch(57.5% 0.232 25 / 0.4), 0 8px 32px -4px oklch(57.5% 0.232 25 / 0.4)`. Secondary button: `background: oklch(15% 0.005 286 / 0.6)`, `border: 1px solid oklch(28% 0.005 286)`, `backdrop-filter: blur(8px)`.

#### HeroSplit (light, hybrid)
- `grid-template-columns: 1.05fr 0.95fr`, gap 64.
- Eyebrow above headline in `--ignite-500`.
- Right column: product image bleeds 8% past column (`transform: translateX(8%)`, `width: 120%`), radius 12, double shadow `0 60px 120px -40px oklch(7.5% 0.003 286 / 0.45), 0 0 0 1px oklch(22% 0.005 286)`.

#### ArchHero (how-it-works dark)
- Padding `180px 32px 140px`, `background: var(--ink-1000)`, border-bottom `1px solid oklch(20% 0.005 286)`.
- Top-right radial glow: `radial-gradient(ellipse, oklch(57.5% 0.232 25 / 0.18), transparent 60%)` positioned `top: 20%, right: -20%, width: 60%, height: 80%`.
- 4-col stat row beneath h1 (Your cloud / Your data / Runs in your account / Framework version).

#### Ontology, Company, Contact heroes (light, compact)
- Padding `168–180px 32px 120–140px`, `background: var(--bg-canvas)`, border-bottom `1px solid var(--border-default)`.
- Two-tone h1, no KPI strip.
- Optional "lead" body block below the h1, separated by a top hairline rule and `padding-top: 40`.

### 5.4 Pillar cards — five styles

#### a) Trapezoid-bars (the canonical pillar)
SVG bar above and below the card. Geometry:
```
viewBox="0 0 1000 16"  preserveAspectRatio="none"  height=12
points="0,14 14,2 986,2 1000,14"  fill="var(--ink-1000)"
```
Used by What Changes (Speed/Quality/Trust) and Invest In Outcomes (Clarity/Control/Confidence). H3: 26px / 700 / -0.02em / 1.15 line-height. Body 16px / 1.55 / `--fg-secondary`. Eyebrow numeric "01"/"02"/"03" in mono 12px / 0.18em / `--fg-tertiary`.

```
▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱   ← top trapezoid bar (black)

  01
  SPEED
  Decisions in minutes instead of weeks…

▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱▱   ← bottom trapezoid bar
```

#### b) Bordered with eyebrow label (operator-stack card pattern)
Used by Problem section's right column. `padding: 32px 28px`, radius 10, `1px solid var(--border-default)`, `background: var(--bg-surface)`. Eyebrow "OPERATOR STACK · TODAY" at top, then a `flex-wrap: wrap` chip cloud with `gap: 6`. Each chip: 12px / 500 / `6px 12px` / radius 4 / 1px subtle border. Below a dashed `border-top: 1px dashed var(--border-default)`, three stat rows (e.g. "0 — shared definition of a job", "4 — different IDs for the same customer", "2 wks — to answer 'which channel actually pays'"). Stat numbers: display 32px / 700 / -0.025em / `--fg-primary`.

#### c) Grid-divided (border-as-gap)
Used by Architecture's Security section. Wrapping container `background: var(--border-default)`; inner cells `background: #fff`, padding `32px 28px`, with `gap: 1` on the parent grid producing crisp 1px hairline cells without per-cell borders.

#### d) Top-border phase row
Used by Ontology lifecycle. `grid-template-columns: 120px 1fr 1.4fr`, gap 48, `padding: 32px 0`, `border-top: 1px solid var(--border-default)`. Left col: phase number in mono 11px / 0.18em / `--fg-tertiary`. Middle col: phase title in display 28px / 600 / -0.02em. Right col: phase body 16px / 1.55 / `--fg-secondary`.

#### e) Dark variant with red glow (compounding right card)
Background `var(--ink-1000)`, color `var(--ink-50)`, with an internal radial gradient overlay `radial-gradient(ellipse, oklch(57.5% 0.232 25 / 0.18), transparent 60%)`. Eyebrow uses `--ignite-400`. Item separators: `1px solid oklch(20% 0.005 286)`.

### 5.5 Stack timeline (Day 0 / 2 / 5 / 7)

```
─────●──────────●──────────●──────────●─────
   Day 0      Day 2      Day 5      Day 7
   …text      …text      …text      …text
```

- Outer grid: `repeat(4, 1fr)`, gap 0.
- Continuous horizontal rule: `position: absolute, top: 28, left: 0, right: 0, height: 1, background: var(--border-default)`.
- Day marker: 10×10 round dot, `background: var(--ignite-500)`, `box-shadow: 0 0 0 4px oklch(57.5% 0.232 25 / 0.15)` (red pulse halo), positioned absolute `top: 24, left: 24` inside each cell.
- Each cell: `padding: 24px 24px 28px`, `border-right: 1px solid var(--border-subtle)` except the last.
- Day label: mono 11px / 0.18em / `--ignite-500`. Title: display 22px / 600 / -0.02em. Body: 14px / 1.5 / `--fg-secondary`.

### 5.6 Stats grids

**Bare 4-col** (HeroStatement, ArchHero, CoreEntities): no card chrome, top-rule only. Eyebrow + value per cell.

**Card 4-col** (Architecture cloud-side): `padding: 20px 22px`, radius 8, `background: var(--bg-surface)`, `border: 1px solid var(--border-default)`. One stat number can be colored `--ignite-500` (e.g. the "data egress" stat).

### 5.7 Pull-quote

Centered. Top-rule `border-top: 1px solid var(--border-subtle)`, `padding-top: 32`, `margin-top: 56`. Display 28px / 500 / -0.025em / 1.2 / `--fg-primary`. The signature pattern: a primary first clause + an italic-tertiary second clause:

> "What looks like fifteen disconnected systems on the surface is one business underneath. *The ontology is the layer where it finally acts like one.*"

### 5.8 Diagrams

The export includes three lifted React diagrams. **`BoundaryDiagram.js` and `OperatorStackList.js` are not present in the export** — the operator-stack pattern lives inline as a bordered chip-cloud card inside `js/SectionsA.js` (see §5.4b).

#### `ArchStackDiagram` (StackDiagram.js)
Isometric 4-platform stack. Cream background card on `var(--bg-canvas)`, max-width 1320, mounted on home + ontology pages (header label "How it all fits together" by default).
- Palette inside the diagram is hardcoded (not token-driven): `ink #0F0F12`, `inkSoft #5A5A60`, `line #C9C5BD`, `lineSoft #E2DDD2`, `cream #FFFFFF`, `creamSide #F4EFE4`, `accent #E11D2E`, `accentBg #FBE0DD`.
- Each platform: rhombus top + 14px-deep side polygon for isometric depth, drop ellipse `opacity 0.05` underneath.
- Stack from top to bottom: Intel/Signal/Agents (top platform with mini "screen" cards — `chart`, `bars`, `alert`, `agent` glyphs) → Ontology (middle, with circle entities) → Data + Models (bottom, with `data` ellipse stack and `model` 4-node graph).
- Pill labels on connectors: rounded rect, mono 9.5px / 0.06em, `hot=true` flag swaps fill to `accentBg` and stroke to `accent`.
- Flows: `FlowBundle` of 16 dashed cubic-bezier paths (`stroke-dasharray: 1 3.5`, width 0.7, opacity 0.55) optionally with chevron arrows at the top.

#### `ArchOntologyScene` (ArchOntologyScene.js)
Isometric line-art "service area" diagram, mounted on Architecture's "How it runs" block. Single SVG `viewBox 1200×600`, framed in a card with top chrome bar reading `YOUR DATA WAREHOUSE  ●  V4.2` (red pulse dot in `--ignite-500`).
- 8 "pills" at top in 2 staggered rows: JOB, CUSTOMER, TECHNICIAN, TRUCK, INVOICE, CALL, PROPERTY, MEMBERSHIP. Each is a 106×32 rounded rect with mono 11px / 2px-tracked label.
- Each pill connects via a `Connector` line to a corresponding isometric primitive: IsoJobSite, IsoHouse, IsoTech, IsoTruck, IsoInvoice, IsoCallTower, IsoHouse(small), IsoMembership. Connector terminates in a 3px dot at the object center.
- Background: `IsoPlatformTopHalf` (the ground plane), central HQ building, plus decorative `IsoSilo` and `IsoTree`.
- Hover state: pill stroke + connector + target dot all flip to `var(--ignite-500)` (sw 1.4 vs 1.0).
- A small `<rect>` "prod" tag at bottom-right.

#### `PlatformStackDiagram` (PlatformStack.js)
Isometric 3-plane "Data → Logic → Actions" stack. Cream `#FFFFFF` background, max-width 1240, mounted on the home page as the "02 The stack" block.
- Headline above SVG: `clamp(40px, 5.2vw, 76px)` with " infrastructure " segment in `var(--ignite-500)`.
- 3 mono pills above the diagram: `[DATA · INFORMATION] + [LOGIC · INTELLIGENCE] + [ACTIONS · OUTCOMES]`, with 30×30 circular `+` connectors between pills. The middle pill (LOGIC) is in `--ignite-500`.
- 3 stacked rhombi (cx=500, halfW=300, halfH=120, planeY = [140, 320, 500]):
  - Bottom plane: filled with `igniteGrad` (linear gradient stop colors `oklch(58% 0.232 25 / 0.95) → oklch(70% 0.18 30 / 0.55) → oklch(80% 0.12 35 / 0.05)`).
  - Middle plane: cream + 4+4 hatched grid lines (lerped diagonals), `stroke 0.9`.
  - Top plane: solid black + 5 nested concentric rhombi (scales 0.85→0.16) with `oklch(38% 0.01 286)` strokes.
- Side connector lines extend from each plane to text labels: "Your decisions & actions" (top, right-aligned at x=90), "Your logic" (middle, left-aligned at x=910), "Your data" (bottom, in `--ignite-500`, x=90).
- Below the SVG: 3-col text grid — `[DATA · Information] / [LOGIC · Intelligence] / [ACTIONS · Outcomes]` — separated by `border-top: 1px solid var(--ink-1000)`, max-width 1100.

### 5.9 Form (Contact + SignIn)

Underline-only inputs, mono labels, asterisk-required, red CTA.

```
FIRST NAME *
─────────────────────────
[       Tom            ]   ← input, no border except 1px border-bottom

WHAT BRINGS YOU HERE?
─────────────────────────
[ Select one…        ▾ ]

TELL US ABOUT YOUR BUSINESS
─────────────────────────────────────────
[                                       ]
[                                       ]   ← textarea, rows=4
[                                       ]

──────────────────────────────────────────
We respond within one business day.   [ Send message → ]
```

Input style:
```
width: 100%; appearance: none; background: transparent; border: none;
border-bottom: 1px solid var(--border-default);
padding: 10px 0; fontSize: 16; color: var(--fg-primary); outline: none;
```
Focus: `border-bottom-color: var(--ignite-500)`. Blur reverts.

Label style: mono 10px / 0.16em / uppercase / `--fg-tertiary` / `marginBottom: 8`. Required asterisk inline in `--ignite-500`.

Submit button: display 16px / 500 / -0.01em, `padding: 14px 28px`, radius 4, `background: var(--ignite-500)`, color `--ink-50`. Label format: `Send message →` / `Sign in →`.

Form footer row: `margin-top: 8, padding-top: 24, border-top: 1px solid var(--border-default)`, `display: flex, justify-content: space-between`. Left: helper text 13px / `--fg-tertiary` ("We respond within one business day."). Right: submit button.

SignIn divergences: 2-col page (dark aside left + light form right), only email + password, "Forgot email?" / "Forgot password?" links below submit (dotted-underlined 14px / `--fg-secondary`).

### 5.10 CTA banner

Dark band, full-width edge to edge. `padding: 160px 32px`, `background: var(--ink-1000)`, color `var(--ink-50)`, `border-top: 1px solid oklch(20% 0.005 286)`. Centered content `max-width: 1100`.

```
                    ●  GET STARTED TODAY

                    Own Your Intelligence.

           Your cloud. Your data. Your Intelligence.
              Deployed in days, not months — and
                 yours from the moment we ship.

         [ Get started today → ]   [ How it works ]
```

Eyebrow color: `--ignite-400`. H2 clamp: `clamp(56px, 7.6vw, 132px)` / 600 / -0.05em / 0.92. Body: 19px / 1.5 / `oklch(75% 0.005 286)` / max-width 640.

Buttons: gap 12, both 15px / 500 / `padding: 16px 28px` / radius 6. Primary `background: var(--ignite-500)`, color `#fff`, double shadow `0 0 0 1px oklch(57.5% 0.232 25 / 0.4), 0 12px 32px -4px oklch(57.5% 0.232 25 / 0.5)`. Secondary transparent + `1px solid oklch(28% 0.005 286)`.

### 5.11 Footer

Full 5-col (`1.5fr 1fr 1fr 1fr 1fr`), gap 48, padding `64px 32px 48px`, `background: var(--ink-1000)`, top border `1px solid oklch(20% 0.005 286)`, max-width 1440.

Column 1 (1.5fr): `assets/logo-white.png` 22×22 + "IgniteIQ" wordmark (display 600 16px / -0.02em / `--ink-50`). Tagline: "Intelligence infrastructure for home services. Own your cloud. Own your data. Own your intelligence." 13px / 1.55 / `oklch(60% 0.005 286)` / max-width 320.

Columns 2–5 headers: mono 10px / 0.18em / uppercase / `oklch(50% 0.005 286)`. Headers: `Platform`, `Company`, `Resources`, `Customers`. Links: 13px / `oklch(75% 0.005 286)`, no decoration, list `gap: 10`.

Copyright row: `margin-top: 32`, mono 11px / 0.14em / uppercase / `oklch(50% 0.005 286)`. Three slots — left: "© 2025 IgniteIQ Inc." · center: "Own Your Intelligence." · right: "Framework v4.2".

### 5.12 Nav

Fixed top, `z-index: 100`, max-width 1440, padding `14px 24px`, gap 36.

Idle (unscrolled): transparent. Scrolled: `backdrop-filter: blur(12px)` plus background `oklch(98.6% 0.001 286 / 0.85)` (light) or `oklch(7.5% 0.003 286 / 0.85)` (dark inverse) and a 1px bottom border.

Logo: 30×30 `assets/logo-{black|white}.png` + "IgniteIQ" wordmark display 600 28px / -0.02em.

Desktop links (≥860px): gap 30, weight 500, color `--fg-secondary` → hover `--fg-primary`, transition `color 140ms var(--ease-standard)`. Right-side CTAs: "Sign in" (link) + "Contact →" (link with arrow).

Mobile drawer (≤860px): right slide-over, `width: min(86vw, 380px)`, transform translateX 100% → 0 at 280ms `var(--ease-standard)`. Drawer items: display 24px / 600 / -0.02em / `border-top: 1px solid var(--border-subtle)` / `padding: 20px 0`. Scrim: `oklch(10% 0.005 286 / 0.4)`, fade 240ms.

### 5.13 Avatar (team member square)

64×64 absolute-positioned square, `border-radius: 6`, layered:

```
1. Base:    linear-gradient(135deg, var(--ink-900), var(--ink-700))
2. Glow:    radial-gradient(circle at 30% 30%,
                            oklch(57.5% 0.232 25 / 0.4),
                            transparent 60%)
3. Initials: monogram (e.g. "SR" for "Scott Rayden")
            font-family: var(--font-display)
            font-size: 22, weight: 600, tracking: -0.02em
            color: var(--ink-50)
```

Set inside a card: `padding: 32px 28px`, `border: 1px solid var(--border-default)`, `background: var(--bg-canvas)`. Below the avatar: name (display 24 / 600 / -0.025em / `--fg-primary`), role (mono 11 / 0.14em / uppercase / `--ignite-500`), bio (15 / 1.6 / `--fg-secondary`).

---

## 6. Motion

### 6.1 Tokens

```
--ease-standard: cubic-bezier(0.2, 0.0, 0.0, 1.0);
--ease-out:      cubic-bezier(0.16, 1, 0.3, 1);
--ease-in:       cubic-bezier(0.7, 0, 0.84, 0);

--duration-instant: 80ms;
--duration-fast:    140ms;
--duration-base:    220ms;
--duration-slow:    360ms;
```

No bounce, no overshoot. Mechanical, infrastructural.

### 6.2 Keyframes

```css
@keyframes iiqPulse {
  0%, 100% { opacity: 1 }
  50%      { opacity: 0.35 }
}

@keyframes iiqRevealIn {
  from { opacity: 0; transform: translateY(16px) }
  to   { opacity: 1; transform: translateY(0) }
}
```

`iiqPulse` (2s ease-in-out infinite) is applied to: eyebrow bullet (6×6 red dot), the "● V4.2" status dot in the ontology scene chrome, day-timeline pulse halo (continuous halo, not the pulse keyframe — but uses the same red rgb at low alpha).

`iiqRevealIn` is the entry transition for `<Reveal>`-wrapped content.

### 6.3 Reveal stagger

`<Reveal delay={ms}>` wraps section content. Common stagger coefficients observed:

- Intro paragraph after a headline: 80–140ms
- 3-pillar grid: `i * 90` or `140 + i * 100`
- 4-step timeline: `i * 100`
- Right-column card next to a left-column headline: 140

### 6.4 Parallax (HeroStatement)

```js
gridY   = scrollY * 0.35   // wide background grid (most visible)
cornerY = scrollY * 0.25   // chrome corners
statsY  = scrollY * 0.12   // stat strip (gentle)
```

Applied via `transform: translate3d(0, ${y}px, 0)`.

### 6.5 LatticeCanvas (HeroCinematic)

46-node ambient lattice (scaled by `intensity`). Velocities `(rand-0.5)*0.18`. 8% chance of a 2.4r node, 10% of a glow node. Pulse `0.6 + sin(t*0.018+seed)*0.4` (period ~6.3s). Connection alpha `(1 - d/maxDist) * 0.55`. Honors `prefers-reduced-motion`.

---

## 7. Imagery & decoration philosophy

- **No stock imagery.** The only photographic asset in the export is `assets/product-executive-overview.png` — a screenshot of the product UI used in HeroSplit, bleeding 8% past its column.
- **No gradients on type, ever.** Type is solid color, two-tone at most.
- **Gradients are reserved** for: (a) the dark-hero red glow `radial-gradient(ellipse, oklch(57.5% 0.232 25 / 0.18), transparent 60%)`; (b) the StackDiagram base-plane `igniteGrad`; (c) the HeroStatement grid mask `radial-gradient(ellipse at 50% 40%, #000 30%, transparent 75%)`; (d) the avatar dark base + red corner glow.
- **No drop shadows on cards.** Shadow is reserved for: primary CTA red glow, HeroSplit product-image lift, ontology-scene card lift `0 30px 60px -30px oklch(7.5% 0.003 286 / 0.18)`.
- **Geometric SVG accents only:** trapezoid bars on pillar cards, isometric rhombi in diagrams, hairline divider rules, monogram avatars, mini-card glyphs (chart/bars/alert/agent/data/model) inside StackDiagram.
- **Avatar style:** square (not circle), 64×64, dark-gradient base + red radial glow + display-font monogram in white.
- **Iconography:** none in body prose. The only "icons" are the 6×6 eyebrow bullet and the 10×10 day-timeline marker. Arrows in CTAs are typographic (`→`), not SVG.
- **Borders are always 1px hairlines**, color from the `--border-*` ramp. Gap-as-border (set parent bg to border color, gap to 1) is preferred over per-cell borders for grid-divided cards.

---

## 8. The 6 cornerstone pages — composition reference

### home (`index.html`)

1. **Nav** — light, switches to dark inverse if `hero === cinematic && !showStatement`.
2. **HeroStatement** — single 140px sentence in red, KPI strip below, parallax grid bg.
3. **WhyItMattersSection** (01) — centered manifesto: "The companies that win the next decade…"
4. **ProblemSection** (02) — left text + right operator-stack chip-cloud card with 3 stats.
5. **PlatformStackDiagram** (02 The stack) — 3 isometric rhombus planes Data→Logic→Actions.
6. **WhatChangesSection** (04) — 3 trapezoid-bar pillars: SPEED / QUALITY / TRUST.
7. **InvestInOutcomesSection** (05) — 3 trapezoid-bar steps: CLARITY / CONTROL / CONFIDENCE.
8. **DeploymentSection** (06) — Day 0 / 2 / 5 / 7 horizontal timeline with red pulse markers.
9. **CTASection** — dark band, "Own Your Intelligence." 132px, dual buttons.
10. **Footer** — full 5-col with customers list.

### how-it-works (`how-it-works.html`)

1. **ArchHero** — dark, "Built on your cloud. Run on your data." + 4-stat row.
2. **ArchTwoHalves** (01) — h2 + ArchOntologyScene isometric service-area diagram.
3. **ArchCloudSide** (04 What you own) — text left + CloudArchDiagram (10 source systems → Fivetran/dbt → warehouse) right.
4. **ArchFrameworkSide** (04 What we bring, dark) — text left + FrameworkDiagram (5 vertical layers, ontology layer accented) right.
5. **ArchOntologyLifecycle** (05) — 4 phase rows (Schema / Resolution / Materialization / Compounding) using top-border 120/1fr/1.4fr grid.
6. **ArchCompoundingDiagram** (06, sunken) — 2 cards: light "Operational data" + dark glow "Patterns & intelligence".
7. **ArchSecurity** (05 Security model) — 6-cell 3-col grid-divided text cards.
8. **ArchDeploymentLifecycle** (08) — 5-phase timeline.
9. **ArchHeadless** (03) — 2-col text + comparison table.
10. **ArchHowItDeploys** (04) — 5-phase icon-dot timeline.
11. **ArchWhatItIsnt** (04) — 3-col h3 + body.
12. **ArchCTA** — dark CTA: "Get started today." + 2 buttons.
13. **ArchFooter** — minimal copyright + back link.

### ontology (`ontology.html`)

1. **OntologyHero** — "Ontology is the nouns and verbs of how your business actually runs." (4-segment alternating tone).
2. **WhatIsAnOntology** (01, sunken) — 3-col bullet-labeled cards: Data / Logic / Actions.
3. **WhyHomeServices** (02, dark) — 3-col bullet tags + titles + bodies.
4. **CoreEntities** (04) — 4-stat strip (200+ / 5,000+ / 25+ / Continuous) + 3-col block grid + centered closing h3 with italic-tertiary clause.
5. **HowItDeploys** (05, sunken) — h2 + 4-stat strip.
6. **ArchStackDiagram** (The flow) — embedded.
7. **CTASection** — shared dark CTA.
8. **Footer** — shared.

### company (`company.html`)

1. **CompanyHero** — "Operators who ran the trucks. Architects who modernized the industry. Innovators building what's next." (two-tone).
2. **WhatWeAre** (01) — "We're not an AI company." 2-col text.
3. **FoundingStory** (02) — left h2 + right 3-paragraph body.
4. **MissionPrinciples** (03 What we believe) — 3-col h3 + body: "Your data is yours." / "Intelligence compounds." / "Infrastructure beats interface."
5. **TeamDeep** (03 The team) — 3-col card grid with 64×64 monogram avatars (Scott Rayden, Ryan Sciandri, Darren Merritt, Josh Scott, Matt Lawler).
6. **ContactSection** — dark CTA.
7. **Footer** — shared.

### contact (`contact.html`)

1. **ContactHero** — "Let's talk. About your data, your decisions, and what compounds when both are yours." (two-tone) + 21px lead body.
2. **ContactBody** — 2-col: left = Office cards (San Diego HQ, San Francisco) + Direct (`hello@igniteiq.com`, `+1 (619) 555-0114`); right = form (firstName/lastName · email/company · role · topic select · textarea · submit). Success state replaces form with "Thanks. We'll be in touch."
3. **Footer** — shared.

### signin (`signin.html`)

Single 2-col page, no nav, no footer.

- **Left aside (dark, `var(--ink-1000)`):** back link to home, eyebrow "● Customer access" in `--ignite-400`, h1 "Welcome back. Your intelligence is waiting." (two-tone), 16px lead body, footer text "v4.2 · running in your cloud". Top-right radial red glow.
- **Right main (light, `--bg-canvas`):** top-right "New here? Contact us" link, centered form (max-width 420) with "Sign in" h2, email + password inputs (underline-only, autoFocus on email), "Sign in →" submit, "Forgot email?" / "Forgot password?" recovery row (dotted underline). Submitted state replaces form with "Signing you in…".

---

## 9. Don'ts

- **Do not paraphrase.** All headlines, body strings, button labels, and footer links are byte-accurate from `exports/latest/`. If a delta must exist, mark it with a `// FIDELITY EXCEPTION:` comment.
- **No body text in red.** `--ignite-500` is reserved for: eyebrow bullets, primary CTA bg, day-timeline marker, required asterisk, focus underline, focus ring, link hover decoration, and accent strokes inside diagrams. Never for paragraph or h2/h3 prose.
- **No icons in body prose.** Only typographic arrows (`→`), the 6×6 eyebrow bullet, and the 10×10 day marker.
- **No drop shadows on cards.** Shadow is reserved for primary CTAs (red glow) and hero feature images.
- **No gradients on type.** Type is solid, two-tone at most.
- **No rounded radii > 16px.** Buttons are 6px, cards 10px, large surfaces 16px, pills only on monogram chrome bars.
- **No body text larger than 22px.** Lead body sits at 19–22px. Anything bigger belongs to a heading.
- **No light-mode dark text on dark sections.** Dark sections (`--ink-1000`) use `--ink-50` for primary text, `oklch(60–78% 0.005 286)` for secondary, and `--ignite-400` (not 500) for eyebrows.
- **No stock imagery, no decorative photography.** The only photographic asset is the product UI screenshot in HeroSplit.
- **No bouncy easing.** All transitions use `--ease-standard` / `--ease-out` / `--ease-in` (cubic-bezier curves with no overshoot).
- **No text on top of dense areas of the LatticeCanvas without the glow gradient.** The radial red glow + bottom darken always sits between the lattice and the headline.
- **No second accent color.** Red is the only accent. State colors (success/warning/info/link-blue) exist as tokens but are reserved for product UI, not marketing surfaces.
- **No skipping `<Reveal>`.** Every section's primary content is wrapped in `<Reveal>`; multi-element groups use staggered delays in 80–140ms increments.
- **No replacing Aeonik with a fallback as the design baseline.** Fallbacks are for unsupported environments only. The display rhythm depends on Aeonik's metrics.

---

*End of document. Source: `/Users/matthewlawler/ignite/exports/latest/`. Framework v4.2.*
