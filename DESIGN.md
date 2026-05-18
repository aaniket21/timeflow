# TimeFlow — Design Specification
**Version:** 1.0.0
**Date:** May 2026
**Status:** Active — Implementation Reference
**Matches PRD:** v2.0.0
**Stack context:** Vue 3 + Inertia.js + Tailwind CSS v3 + Vite

> This document is the single source of truth for every visual and interaction decision in TimeFlow.
> Every page, component, state, animation, spacing value, and color token is defined here.
> When PRD and DESIGN.md conflict, DESIGN.md wins for visual decisions; PRD wins for data/logic decisions.

---

## Table of Contents

1. [Design Philosophy](#1-design-philosophy)
2. [Color System](#2-color-system)
3. [Typography](#3-typography)
4. [Spacing & Grid](#4-spacing--grid)
5. [Aspect Ratios & Layout Dimensions](#5-aspect-ratios--layout-dimensions)
6. [Component Library](#6-component-library)
7. [Animation & Motion](#7-animation--motion)
8. [Page-by-Page Specifications](#8-page-by-page-specifications)
   - 8.1 App Shell (Topbar + Sidebar)
   - 8.2 Dashboard
   - 8.3 Timer Page
   - 8.4 Focus Mode (Fullscreen Overlay)
   - 8.5 Analytics — Daily View
   - 8.6 Analytics — Weekly View
   - 8.7 Analytics — Monthly View
   - 8.8 Projects Page
   - 8.9 Timetable Page
   - 8.10 Habits Page
   - 8.11 Goals Page (includes Exams)
   - 8.12 Achievements Page
   - 8.13 Leaderboard Page
   - 8.14 Reports Page
   - 8.15 Settings Page
   - 8.16 Auth Pages (Login / Register / Reset)
   - 8.17 Onboarding Wizard
9. [Modals & Overlays](#9-modals--overlays)
10. [Dark Mode](#10-dark-mode)
11. [Responsive & PWA Behavior](#11-responsive--pwa-behavior)
12. [Iconography](#12-iconography)
13. [Empty States](#13-empty-states)
14. [Backend Data Mapping](#14-backend-data-mapping)
15. [Tailwind Config Reference](#15-tailwind-config-reference)

---

## 1. Design Philosophy

### Warm Minimal

TimeFlow's confirmed visual direction is **warm minimal** — derived from the reference screenshot. This means:

- **White cards on a warm parchment background.** Not flat white on white, not clinical grey-on-grey. The background (`#F5F0E8`) has a slight warmth that makes the white cards feel crisp and present rather than sterile.
- **Data IS the decoration.** No illustrative backgrounds, gradients, noise textures, or hero images on any app page. Every pixel of color either communicates state, encodes data, or provides interactivity feedback.
- **Gamification recedes to the right moment.** The streak flame, XP bar, and badge reveals are vivid when earned. Between those moments, they exist as quiet supporting elements — not the focus.
- **One primary action per screen.** Every page has one thing it wants the user to do. Dashboard: start a session. Timer: stop or start. Analytics: understand. Never two competing CTAs at the same visual weight.

### Visual Hierarchy Rules

1. Page title → largest (17–20px, weight 800)
2. Card title → medium (13–15px, weight 700)
3. Data values → JetBrains Mono, sized by importance (13–44px)
4. Labels → smallest (9–11px, weight 600–700, uppercase, letter-spacing 0.08em)
5. Body / description text → 12–13px, weight 400–500

### Design Anti-Patterns (Never Do These)

- Never use a gradient background on the app shell, sidebar, or card body
- Never use drop-shadows on cards (border only: `1px solid rgba(80,60,20,0.10)` in light mode)
- Never use more than 3 accent colors on a single card
- Never use ALL CAPS for anything except section labels
- Never center-align body text or paragraphs
- Never use font-weight 900 — max is 800
- Never put more than 6 data points on a single small card
- Never show empty state as a blank white box — always include an action

---

## 2. Color System

### Light Mode (Primary — Confirmed Design Direction)

This is the default theme for TimeFlow. All pages are designed light-mode-first.

```
Token Name              Hex Value        Usage
--------------          ---------        -----
bg-page                 #F5F0E8          Page/app background (warm parchment)
bg-card                 #FFFFFF          All card surfaces
bg-card-alt             #FAF7F2          Nested elements inside cards
bg-sidebar              #F0EAE0          Sidebar + topbar background
bg-hover                #FAF7F2          Hover state on nav items, table rows

border-default          rgba(80,60,20, 0.10)   Card borders, dividers
border-emphasis         rgba(80,60,20, 0.18)   Active borders, focused inputs
border-strong           rgba(80,60,20, 0.28)   Explicit separators

text-primary            #1C1917          Main content text
text-secondary          #6B6256          Supporting text, labels
text-hint               #A89E8E          Placeholder, disabled, metadata
```

### Dark Mode (Secondary)

```
Token Name              Hex Value        Usage
--------------          ---------        -----
bg-page                 #0C0C10          Page background
bg-card                 #13131A          Card surfaces
bg-card-alt             #18181F          Nested elements, input fields
bg-sidebar              #13131A          Sidebar + topbar
bg-hover                #1F1F28          Hover states

border-default          rgba(255,255,255, 0.07)   Card borders
border-emphasis         rgba(255,255,255, 0.12)   Active, focused
border-strong           rgba(255,255,255, 0.18)   Explicit separators

text-primary            #EEEAF4          Main text
text-secondary          #8B879E          Supporting text
text-hint               #3E3C4A          Metadata, placeholders
```

### Accent Colors (Both Modes — Identical Hex)

```
Token          Hex        Opacity variants          Usage
------         ---        ----------------          -----
violet         #7C5CFC    /12 bg, /22 border        Primary action, XP, progress, active nav
               Light text: #5B3FD4 | Dark text: #A78BFA

mint           #0ECFA4    /12 bg, /25 border        Live timer, goal completion, success
               Light text: #0A8A6C | Dark text: #0ECFA4

amber          #F5A623    /12 bg, /28 border        Streak, warnings, daily challenge
               Light text: #92400E | Dark text: #F5A623

rose           #F06292    /10 bg, /22 border        Personal category, health habits
               Light text: #BE185D | Dark text: #F06292

sky            #38BDF8    /10 bg, /28 border        Exam calm (>14 days), info chips
               Light text: #0369A1 | Dark text: #38BDF8

red            #EF4444    /10 bg, /28 border        Exam urgent (<=7 days), broken streak, budget
               Light text: #991B1B | Dark text: #EF4444
```

### Color Usage Rules

- **Violet** is the ONLY color used for primary interactive elements (buttons, active nav, focus rings, XP).
- **Mint** is used EXCLUSIVELY for: live timer state, session completion success, goal-hit state.
- **Amber** is used EXCLUSIVELY for: streak counter, streak-related elements, daily challenge, warnings.
- **Red** is used EXCLUSIVELY for: destructive actions, urgent exam chips (<=7 days), budget exceeded, broken streak animation.
- **Sky** is used EXCLUSIVELY for: calm exam chips (>14 days), informational states.
- **Rose** is used EXCLUSIVELY for: personal/health category tagging.

### Semantic Color Application

```
Focus ring stroke:           Violet (#7C5CFC) — always
XP bar fill:                 Violet gradient (#7C5CFC → #A78BFA)
Active session indicator:    Mint (#0ECFA4) with /12 bg overlay
Streak flame number:         Amber (#F5A623)
Exam chip >14d:              Sky bg/border
Exam chip 7-14d:             Amber bg/border
Exam chip <=7d:              Red bg/border with opacity pulse animation
Exam day banner:             Red bg, full width above dashboard content
Goal hit flash:              Mint
Level up overlay:            Violet confetti
Badge unlock:                Amber glow behind badge icon
```

---

## 3. Typography

### Font Stack

```css
--font-sans:  'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-mono:  'JetBrains Mono', 'Cascadia Code', 'Fira Code', monospace;
```

**Google Fonts import (place in <head>):**
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
```

### Type Scale

```
Role                    Size    Weight    Font          Usage
----                    ----    ------    ----          -----
Page greeting           17px    800       Jakarta Sans  "Good morning, Arjun"
Page title              17px    800       Jakarta Sans  Section headings (Timer, Analytics, etc.)
Card title              14px    700       Jakarta Sans  Card headings
Stat value large        24–44px 700       Jetbrains Mono  Timer display, hero numbers
Stat value medium       18–22px 700       JetBrains Mono  Daily stats, XP count
Stat value small        13–16px 600       JetBrains Mono  Inline numbers, durations
Body text               12–13px 500       Jakarta Sans  Card descriptions, session names
Supporting text         11px    500       Jakarta Sans  Secondary info, category labels
Section label           9.5px   700       Jakarta Sans  UPPERCASE, letter-spacing 0.09em
Metadata / hint         10px    400       Jakarta Sans  Timestamps, placeholders
Button label            12px    600       Jakarta Sans  Action buttons
Input text              13px    400       Jakarta Sans  Form fields
Tab label               12px    600       Jakarta Sans  Analytics tab bar
Badge text              9.5px   700       Jakarta Sans  Status chips, level badge
```

### Specific Type Rules

- **Timer displays** always use JetBrains Mono. This prevents layout shift on digit changes.
- **All numeric statistics** use JetBrains Mono — hours logged, XP count, streak days, session duration, focus score.
- **Section labels** are always: uppercase, letter-spacing 0.09em, 9–10px, weight 700, text-hint color.
- **Section labels are NOT headers** — they do not use h1–h6 tags. Use `<p class="section-label">` or `<span>`.
- **Never use font-weight 600 for body text** inside cards — use 500. Weight 600+ is reserved for labels, values, and buttons.
- **Line height:** Body text 1.5, stat values 1.0–1.1, section labels 1.0.

---

## 4. Spacing & Grid

### Base Unit: 4px

All spacing is a multiple of 4px.

```
4px   — xs: icon-to-label gap, inline tight spacing
8px   — sm: gap inside a stat row, between dots
12px  — md: internal card padding (compact mode)
14px  — md+: standard card padding (horizontal)
16px  — lg: page content gap, card-to-card gap
20px  — xl: section-to-section gap on pages with many sections
24px  — 2xl: modal padding, page padding on wide screens
```

### Page Layout Padding

```
Main content area padding:    16px all sides (desktop)
Main content area padding:    12px all sides (tablet)
Main content area gap:        12px between stacked cards
Section-to-section gap:       12px (same as card gap — no extra spacing)
```

### Card Internal Spacing

```
Card padding:                 13px top/bottom, 15px left/right
Card title-to-content gap:    10px
Card section label margin:    0 0 6px 0
Divider in card (hr):         1px, full bleed to card edges (negative margin -15px)
```

### Grid Columns

```
Sidebar width:               190px (fixed, not resizable)
Main content:                flex: 1 (takes remaining width)
Topbar height:               48px
Min app height:              700px
Min app width:               900px (below this: mobile layout triggers)

Two-column card grid:        1fr 1fr, gap 10px
Three-column stat grid:      repeat(3, 1fr), gap 9px
Quick-start row:             repeat(3, 1fr), gap 7px
Timetable columns:           44px (time) + repeat(7, 1fr)
Analytics chart area:        full width of main, height 200–240px
```

---

## 5. Aspect Ratios & Layout Dimensions

This section is critical for implementation — every visual element has a defined size.

### App Shell

```
Total app:                   min-width 900px, min-height 700px
Topbar:                      height 48px, full width
Sidebar:                     width 190px, height = viewport - 48px (topbar)
Main content:                width = viewport - 190px, padding 16px
```

### Dashboard Components

```
Focus ring SVG:              72×72px. Circle cx/cy=36, r=30. stroke-width 5.
                             stroke-dasharray = 2π×30 = 188.5 ≈ 188
                             At 100% goal: stroke-dashoffset = 0
                             At 63% goal: stroke-dashoffset = 188 × (1 - 0.63) = 69.6 ≈ 70
                             Formula: dashoffset = 188 × (1 - percentage/100)

Timer display:               font-size 28px, JetBrains Mono 600
                             Width of "00:00:00" = fixed (mono font) — no layout shift

Live timer card:             height auto, min-height 100px
                             Mint overlay: full inset, rgba(14,207,164,0.12)

Stat cards (3-col row):      height auto, min-height 80px
                             Stat value: 20px mono 700
                             Delta chip: 9.5px, 18px height

XP bar:                      height 4px, border-radius 3px, full card width
                             Fill width = (xp_total - level_min_xp) / (next_level - level_min_xp) × 100%

Streak widget (sidebar):     width = sidebar width - 18px margins
                             Flame emoji: 22px
                             Day count: 22px mono 700
                             Week dots: 8px diameter each, 3px gap

Heatmap grid:                14 columns × 6 rows
                             Each cell: 9×9px, border-radius 2px, 3px gap between cells
                             2px gap between columns
                             Total width ≈ 14×9 + 13×2 = 152px (left-aligned)

Timetable strip (dashboard): height auto, horizontal scroll
                             Each block: min-width 82px, border-radius 8px, padding 7px 10px
                             Active block: border-width 1.5px

Habit circles:               19×19px, border-radius 5px
                             7 per habit (Mon–Sun)
                             Gap between circles: 3px
                             Habit name column: width 80px fixed

Daily plan checkboxes:       17×17px, border-radius 5px
                             Each task row: height auto, padding 7px 0

Quick start buttons:         height 60px, full grid column width
                             Icon: 16px
                             Label: 10.5px
```

### Timer Page Components

```
Pomodoro ring SVG:           200×200px. Circle cx/cy=100, r=88.
                             stroke-dasharray = 2π×88 = 552.9 ≈ 553
                             stroke-width 8
                             Countdown: dashoffset animates from 0 (full) to 553 (empty) over interval duration

Work interval indicator:     Four dots, 10×10px each, 8px gap
                             Done: violet fill. Active: mint fill + glow. Pending: border only.

Session notes textarea:      width 100%, min-height 60px, max-height 120px
                             border-radius 8px
```

### Analytics Components

```
Daily hourly bar chart:      Height 160px total.
                             24 bars (one per hour). Width = (chart_width - 23×2px gap) / 24
                             Each bar: min-height 2px (to show zero-hour baseline)
                             Bar color = project/category color (most-used in that hour)

Weekly line chart:           Height 180px. Width = full card width - 32px padding.
                             Two lines: logged hours (violet) + daily goal (dashed amber)
                             Data points: 7 (Mon–Sun). Point radius: 4px.

Category donut chart:        120×120px SVG. Stroke-width 20. Gap between segments: 3deg.
                             Legend: right of donut, 12px labels with color dot

Focus score display:         Large number: 36px mono 700. Color: violet if >=70, amber if 40-69, red if <40.
                             Circular arc background: 80px ring same as focus ring

Monthly trend line:          Height 200px. Shows 30 days. Violet line + 7-day rolling avg (dashed mint).
                             Area fill: violet at 10% opacity under the line.
```

### Focus Mode (Fullscreen)

```
Overlay:                     100vw × 100vh. Background: #0A0A0E (always dark, never light).
Timer display:               52px mono 600, centered
                             Letter-spacing 0.03em
Sub-label:                   12px, color rgba(255,255,255,0.4)
Pomodoro dots:               10px diameter, 7px gap, centered below timer
                             Done: violet fill. Active: mint fill + box-shadow glow. Pending: rgba border.
Control buttons:             Primary (pause): background violet, height 40px, border-radius 9px
                             Secondary (skip): background rgba(255,255,255,0.07), same height
Sound buttons:               5px 10px padding, 10.5px font, border-radius 7px, border rgba border
Quote block:                 border-left 2px solid violet, padding 12px 14px, 12px italic text
```

### Timetable Page (Full Grid)

```
Grid header row:             height 32px
Time column:                 width 44px, 9px mono text, right-aligned
Day columns:                 equal width, header shows day abbreviation (9-10px bold)
Time slots:                  height 36px per 30-minute slot (6AM–11PM = 34 slots = 1224px total)
                             Total grid height: 34×36 + 33×1 (borders) = 1257px (scrollable)
                             Viewport shows approximately 14 slots at a time = ~520px visible area
Event blocks:                Spans rows based on (duration_minutes / 30) slots
                             border-radius 5px, padding 3px 5px
                             Name: 10px bold. Time: 8.5px mono.
Current time line:           1px, color red (#EF4444), full width across all day columns
                             Position: calculated from current time within visible range
```

### Reports Page

```
Report card:                 Standard card. Left: title + date range. Right: status badge + download/share.
PDF preview (if embedded):   Aspect ratio 8.27:11.69 (A4). Max width 640px. Border: 1px card border.
Report generation modal:     Width 480px, centered. Height auto. See modal specs section.
```

### Settings Page

```
Settings layout:             Two-column on desktop: left nav 200px + right content area
                             Left nav: list of setting categories
                             Right content: one section visible at a time
Avatar upload area:          80×80px circle. Upload overlay appears on hover.
Toggle switches:             Width 40px, height 20px, thumb 14×14px
Range sliders:               Native HTML range. Track height 4px. Thumb 18px.
```

---

## 6. Component Library

Each component below is the authoritative spec. Vue component filenames shown in parentheses.

### 6.1 Card (`Card.vue`)

```
Background:   bg-card (white light / #13131A dark)
Border:       1px solid border-default
Border-radius: 11px
Padding:      13px 15px
Transition:   background 0.35s, border-color 0.35s

States:
  Default:    as above
  Hover (interactive cards): bg-hover, border-emphasis
  Active:     scale(0.99) on mousedown, 50ms ease
```

### 6.2 Section Label (`SectionLabel.vue`)

```
Font:         Plus Jakarta Sans 9.5px / 700 / uppercase / letter-spacing 0.09em
Color:        text-hint
Margin:       0 0 6px 0
Display:      block
No border, no background.
```

### 6.3 Focus Ring (`FocusRing.vue`)

```
SVG: 72×72px. viewBox="0 0 72 72". transform: rotate(-90deg) on SVG element.

Track circle:  cx=36 cy=36 r=30, stroke-width=5, fill=none
               stroke: light mode rgba(80,60,20,0.12) / dark mode #1F1F28

Progress circle: cx=36 cy=36 r=30, stroke-width=5, fill=none, stroke-linecap=round
               stroke: #7C5CFC (violet) — changes based on % via JS:
                 0–33%:   #EF4444 (red)
                 34–66%:  #F5A623 (amber)
                 67–99%:  #0ECFA4 (mint)
                 100%+:   #7C5CFC (violet)
               stroke-dasharray: 188
               stroke-dashoffset: 188 × (1 - pct/100) — computed in Vue computed property

Center text: absolute positioned, flex column, center aligned
             Hours value: 13px, JetBrains Mono 600, text-primary
             Label "of Xh": 9px, text-hint

Animation: On mount, dashoffset animates from 188 to computed value.
           Duration 1.2s, cubic-bezier(0.4, 0, 0.2, 1)
           Use CSS animation with @keyframes, not JS interval.

Props: { currentHours: Number, goalHours: Number }
Computed: percentage = Math.min(110, (currentHours / goalHours) × 100)
          dashoffset = 188 × (1 - Math.min(1, currentHours / goalHours))
          strokeColor = percentage < 34 ? '#EF4444' : percentage < 67 ? '#F5A623' : percentage < 100 ? '#0ECFA4' : '#7C5CFC'
```

### 6.4 Live Timer (`LiveTimer.vue`)

```
Card style with mint glow overlay (rgba(14,207,164,0.12)) as ::before pseudo-element.
Border: 1px solid rgba(14,207,164,0.22)

Top row:
  Left:  Pulse dot (6×6px, background mint, animation pd 1.4s) + "LIVE" text (9.5px, 700, mint, letter-spacing 0.05em)
  Right: Stop button

Project label: 10px, 600, mint color, letter-spacing 0.03em, margin-top 8px
Timer value: 28px, JetBrains Mono 600, text-primary, line-height 1.1, margin-top 3px
Progress bar: 3px height, border-radius 2px
              Background: light #F0EAE0 / dark #1F1F28
              Fill: mint, width = (elapsed_seconds / session_target_seconds × 100)%
              Session target = user's daily_goal_hours converted to seconds

JS: setInterval every 1000ms
    Reads started_at from Pinia activeSession store
    Computes elapsed = Date.now()/1000 - started_at.unix()
    Formats as HH:MM:SS

Props: { session: Object|null }
Emits: stop() — triggers POST /api/sessions/{id}/stop
```

### 6.5 XP Bar (`XPBar.vue`)

```
Container: full width of parent card
Level row: flex, space-between
  Left: level name (13px, 700, text-primary)
  Right: level badge (9.5px, 700, violet bg/border chip)

Track: height 4px, border-radius 3px
       Background: light rgba(80,60,20,0.12) / dark #1F1F28

Fill: height 100%, border-radius 3px
      Background: linear-gradient(90deg, #7C5CFC, #A78BFA)
      Width: computed as percentage (see formula above)
      Animation: width from 0% to computed on mount
                 Duration 1.2s, delay 0.4s, cubic-bezier(0.4,0,0.2,1)

Below bar: flex, space-between, 9.5px, JetBrains Mono, text-hint
           Left: "{xp_total} XP"
           Right: "{next_level_xp} next"

Props: { xpTotal: Number, level: Number }
Computed from config/gamification.php levels array:
  currentLevelMin = levels[level].min_xp
  nextLevelMin = levels[level+1].min_xp (or Infinity if max level)
  fillPercent = (xpTotal - currentLevelMin) / (nextLevelMin - currentLevelMin) × 100
```

### 6.6 Streak Widget (`StreakWidget.vue`)

```
Container: border-radius 10px, padding 12px 10px, text-align center
           Light: bg #E8E0D0, border rgba(80,60,20,0.12)
           Dark: bg #18181F, border rgba(255,255,255,0.07)

Flame emoji: 22px, display block, animation flm 2.5s ease-in-out infinite
             @keyframes flm: 0%,100% scaleY(1) rotate(-1deg) / 50% scaleY(1.1) rotate(1deg)

Streak number: 22px, JetBrains Mono 700, color amber (#F5A623)
Streak label: 10px, text-hint, margin-top 2px

Week dots row: flex, center, gap 3px, margin-top 8px
  Each dot: 8×8px, border-radius 50%
  Empty:    light rgba(80,60,20,0.15) / dark rgba(255,255,255,0.1)
  Done:     amber (#F5A623) + box-shadow 0 0 4px rgba(245,166,35,0.5)
  Today:    mint (#0ECFA4) + box-shadow 0 0 4px rgba(14,207,164,0.5)

Data: 7 dots for Mon–Sun of current week. Today's dot uses 'now' class.
      Dot is 'done' if user logged a session on that day.
      Read from Inertia props: { streak: { current, longest, week_days: [bool×7] } }

Animation: if streak breaks (streak drops to 0):
  shake animation on the widget + dot turns red for 1 frame then fades
```

### 6.7 Exam Chips (`ExamChips.vue`)

```
Container: flex, gap 6px, flex-wrap wrap

Each chip: display inline-flex, align-items center, gap 5px
           padding 4px 10px, border-radius 20px, font-size 11px, font-weight 600
           border 1px solid
           cursor pointer (navigates to Goals/Exams page on click)

Urgency variants:
  calm (>14 days):  bg sky/10, border sky/28, text sky (light: #0369A1)
                    Icon: ti-school
  warn (7-14 days): bg amber/12, border amber/28, text amber (light: #92400E)
                    Icon: ti-alert-triangle
  hot (<=7 days):   bg red/10, border red/28, text red (light: #991B1B)
                    Icon: ti-alert-circle
                    Animation: opacity pulse 1.8s ease-in-out infinite (1 → 0.6 → 1)
  banner (0 days):  Full-width red banner above all dashboard content (not a chip)
                    Background: rgba(239,68,68,0.15)
                    Border-bottom: 1px solid rgba(239,68,68,0.3)
                    Padding: 8px 16px
                    Text: "📅 Your [Subject] exam is TODAY — good luck!" — center aligned

Add exam chip:      dashed border, text-hint color, ghost style
                    Clicking opens exam creation modal

Props: { exams: Array<{subject, exam_date, days_remaining, urgency}> }
Backend: GET /api/exams → ExamController@upcoming
```

### 6.8 Timetable Strip (`TimetableStrip.vue` — Dashboard version)

```
Container: horizontal scroll, flex, gap 6px, overflow-x auto
           Scrollbar hidden (scrollbar-width: none / ::-webkit-scrollbar display none)

Each block: min-width 82px, border-radius 8px, padding 7px 10px
            border 1px solid (color-specific)
            flex-shrink 0

Block types & colors:
  class:    background rgba(239,68,68,0.07), border rgba(239,68,68,0.22), text #EF4444
  study:    background rgba(124,92,252,0.08), border rgba(124,92,252,0.25), text #A78BFA (light: #5B3FD4)
  break:    background rgba(245,166,35,0.06), border rgba(245,166,35,0.20), text #F5A623 (light: #92400E)
  personal: background rgba(240,98,146,0.07), border rgba(240,98,146,0.20), text #F06292 (light: #BE185D)

Active block (current time within start/end):
  border-width 1.5px (instead of 1px)
  Prepend: pulse dot (6×6px mint) before time text

Block content (top to bottom):
  Row: [pulse dot if active] + time text (9px mono, block color)
  Block name (11px, 700, block color, line-height 1.2)
  Type label (9px, block color at 70% opacity, emoji + type name)

"Up next" label: shown on the chronologically next upcoming block
  Small badge above time: "up next" in 9px amber on amber/12 bg

Backend: GET /api/timetable/today
         Returns blocks ordered by start_time, with is_active bool computed server-side
```

### 6.9 Habit Row (`HabitRow.vue` — Dashboard version)

```
Container: one row per habit
           flex, align-items center, gap 7px
           padding 7px 0, border-bottom 1px (card divider color)
           Last child: no border

Habit name: 80px fixed width, flex-shrink 0
            11px, 600, text-primary
            Emoji icon prefix

Circles container: flex, gap 3px
  7 circles (Mon–Sun):
    Size: 19×19px, border-radius 5px
    Empty: bg bg-card-alt, border border-default
    Done: bg = habit.color (custom per habit), border transparent, check icon (9px, white)
    Past days: non-interactive (pointer-events none), opacity 0.7 if done, 0.4 if missed
    Today: fully interactive (onclick triggers /api/habits/{id}/log)
    Future: bg bg-card-alt 50% opacity, cursor not-allowed, no event

    Click animation: scale 0.9 → 1.05 → 1 on check, 150ms total

  Weekend column (Sat/Sun): font-size 9px 'S' label shown inside if no log
                             Shown at 40% opacity — visual indicator it may not apply

Per-habit streak: margin-left auto
                  10px, JetBrains Mono 700, amber color
                  Format: "🔥 {n}"

Backend data: GET /api/habits/logs?from=week_start&to=today
              Plus: habits.current_streak from GET /api/habits
```

### 6.10 Daily Plan Card (`DailyPlanCard.vue`)

```
Card header: flex, space-between
  Left: section label "Today's 3 priorities"
  Right: XP badge (violet bg/border chip): "⚡ +30 XP on completion"

3 task rows (plan-item):
  flex, align-items center, gap 9px
  padding 7px 0
  border-bottom 1px solid (card divider color)
  Last: no border

  Checkbox: 17×17px, border-radius 5px, border 1.5px solid border-emphasis
            Done: background mint, border mint, check icon (10px, white)
            Click: toggles done state → PATCH /api/daily-plans/{date}/task/{index}
            Transition: background-color 200ms ease, transform scale 0.9→1 on check

  Task text: flex 1, 12.5px, 500, text-primary
             Done state: text-decoration line-through, text-hint color

  If no plan exists for today: input prompt appears
    Three text inputs (one per task), "Let's plan your day →" button
    POST /api/daily-plans on submit

If all 3 done: success bar appears below card (mint bg, "🎉 Plan complete! +30 XP earned")
               Triggers confetti burst (small, localized, 1s duration)
```

### 6.11 Session Summary Modal (`SessionSummaryModal.vue`)

```
Overlay: rgba(0,0,0,0.5) backdrop, click-outside does NOT close (user must interact)

Modal: width 420px, centered, border-radius 14px
       bg-card, border border-emphasis

Header: bg-card-alt, padding 16px 20px
  Left: project color dot (10px) + project name (14px, 600) + category (11px, text-hint)
  Right: "Completed" badge (mint bg/border)

Duration hero: text-center, padding 18px 0 20px, border-bottom 1px divider
  Label: "Total duration" (10px, uppercase, letter-spacing 0.08em, text-hint)
  Time: 44px, JetBrains Mono 600
  Sub: "Started 10:36 AM · Ended 12:00 PM" (12px, text-secondary)

Stats grid (3 col): focus_score | pomodoros | interruptions
  Each: bg-card-alt, border-radius 10px, padding 12px 10px, text-center
  Value: 18px mono 700 | Label: 10px text-hint

XP earned panel: violet bg/border, padding 14px 16px, border-radius 12px
  Left: violet square icon (36×36, border-radius 10px, bolt icon white)
  Right: "+{n} XP earned" (16px, mono 700, #A78BFA light/#5B3FD4 dark)
         Reason text (11px, text-hint)
         Mini XP bar showing bar progress AFTER this session

Badge unlock (if any): amber bg/border, padding 12px 14px, border-radius 12px
  Icon (32×32, amber bg, award icon) + badge name (13px, 600, amber) + description (11px, text-hint)
  If multiple badges: stacked vertically

Notes textarea: label + 2-row textarea, placeholder "What did you work on?"

Action buttons: 2-col grid
  Left: "New session" (secondary style)
  Right: "View analytics" (violet primary)
```

### 6.12 Level Up Modal (`LevelUpModal.vue`)

```
Overlay: rgba(0,0,0,0.7), covers full viewport
         z-index: 9999

Canvas confetti: absolute, full viewport, pointer-events none
                 Colors: #7C5CFC, #A78BFA, #0ECFA4, #F5A623, #F06292
                 Duration: 3 seconds, gravity applied
                 Library: canvas-confetti (import from CDN)

Center modal: width 360px, border-radius 16px, bg-card, padding 32px 24px, text-center
              Animation: scale 0.8→1, opacity 0→1, 400ms cubic-bezier(0.34,1.56,0.64,1)

Level badge ring: 80×80px circle, background violet, center
                  Level number: 28px, JetBrains Mono 700, white
                  Outer ring: 4px animated stroke, 360deg on mount, 800ms

"Level {n}" text: 24px, 800, text-primary, margin-top 16px
Title text: 18px, 600, violet
Subtitle: 12px, text-secondary, "You unlocked: {feature_unlocked}"

Dismiss: auto-dismiss after 4 seconds OR click anywhere
         Fade-out + scale-down 0.95, 300ms
```

---

## 7. Animation & Motion

### Principles

1. **Purposeful** — every animation communicates state change or rewards achievement.
2. **Fast** — interactive feedback < 200ms. Celebratory animations 600ms–3s.
3. **No bounce on data updates** — only on celebrations. Data updates use ease-out.
4. **Reduced motion** — all animations respect `prefers-reduced-motion: reduce`. If set: disable all animations except fade (opacity only, no transform).

### Animation Catalog

```
Name                  Target              Duration    Easing                    Details
----                  ------              --------    ------                    -------
Page card stagger     All dashboard cards 0.05s/card  ease                      opacity 0→1, translateY 12px→0
                                                                                Delay multiplier: card index × 50ms
Focus ring draw       Focus ring SVG      1.2s        cubic-bezier(.4,0,.2,1)   stroke-dashoffset full→computed
XP bar fill           XP bar              1.2s        cubic-bezier(.4,0,.2,1)   width 0→computed, 0.4s delay
Timer bar fill        Live timer bar      1.0s        ease-out                   width 0→computed on session start
Streak flame          Streak emoji        2.5s loop   ease-in-out               scaleY(1) rotate(-1deg) ↔ scaleY(1.1) rotate(1deg)
Pulse dot             Live timer, blocks  1.4s loop   ease-in-out               opacity+scale 1→0.4+0.7 →1
Hot chip pulse        Exam <=7d chips     1.8s loop   ease-in-out               opacity 1→0.6→1
Habit check           Habit circle        150ms       ease                       scale 0.9→1.05→1 + bg-color fill
Plan task check       Task checkbox       200ms       ease                       scale 0.9→1 + bg-color fill
Level up modal in     LevelUpModal        400ms       cubic-bezier(.34,1.56,.64,1) scale 0.8→1 + opacity
Level up modal out    LevelUpModal        300ms       ease-in                   scale 0.95→0.8 + opacity 0
Confetti              Canvas              3000ms      gravity physics            canvas-confetti library
Session summary in    SessionSummaryModal 350ms       cubic-bezier(.4,0,.2,1)   translateY 20px→0 + opacity
XP counter            XP number           600ms       ease-out                   JS counter 0→xp_gained
Badge reveal          Badge card          400ms each  ease                       opacity 0→1, translateX 20px→0, 200ms stagger
Streak break          Streak widget       400ms       ease                       shake animation x-axis ±4px × 3
Focus ring color      Ring stroke         instant     none                       No transition on color change
Heatmap cells         Heatmap             on hover    100ms                      transform scale(1.5)
Quick-start hover     Buttons             150ms       ease                       border-color, bg transition
Dark mode toggle      Entire app          350ms       ease                       All bg/color/border via CSS transition
```

### Transition Declarations (global CSS)

```css
.card, .ni, .ttb, .hc, .chk {
  transition: background-color 0.35s ease, border-color 0.35s ease, color 0.35s ease;
}
```

---

## 8. Page-by-Page Specifications

### 8.1 App Shell (Topbar + Sidebar)

**Topbar** (height: 48px, full width, grid-column 1/-1):

```
Left:   Logo mark (26×26px violet square, border-radius 7px, clock icon white 13px)
        + "TimeFlow" wordmark (15px, 800, letter-spacing -0.4px)

Right:  XP Chip (11px mono 700, violet bg/border, "⚡ {xp_total} XP")
        Bell icon (ti-bell, 16px, text-secondary) + red dot (5px) if unread notifications
        Avatar (28×28px circle, gradient violet→mint, initials 11px 800 white)

Light:  bg #F0EAE0, border-bottom rgba(80,60,20,0.12)
Dark:   bg #13131A, border-bottom rgba(255,255,255,0.07)
```

**Sidebar** (width: 190px, padding: 12px 9px, flex column):

```
Nav groups: "Main", "Grow", "Export" section labels (9px, 700, uppercase, text-hint)
            Padding: 8px 8px 3px (top of each group)

Nav items: height auto, padding 7px 9px, border-radius 8px, gap 8px, font-size 12px, weight 500
  Icon: ti-* at 14px, aria-hidden
  Label: text
  Badge (projects count): 9px, 700, violet bg, white text, padding 1px 5px, border-radius 8px, margin-left auto

  States:
    Default: text-secondary, bg transparent
    Hover: text-primary, bg bg-hover
    Active: text violet (light #5B3FD4 / dark #A78BFA), bg violet/10, border violet/22

Navigation items in order:
  MAIN: Dashboard (ti-layout-dashboard), Timer (ti-player-play),
        Analytics (ti-chart-bar), Projects (ti-folder) [+ count badge]
  GROW: Achievements (ti-trophy), Goals (ti-target), Leaderboard (ti-podium)
  EXPORT: Reports (ti-file-analytics)

Bottom (margin-top auto): Streak Widget (see §6.6)
```

---

### 8.2 Dashboard

**Route:** `/dashboard`
**Inertia component:** `Pages/Dashboard.vue`
**Backend:** `DashboardController@index`

**Inertia props passed:**
```
user: {name, level, xp_total, streak_current, streak_shield, last_active_date, daily_goal_hours}
today_stats: {total_seconds, session_count, avg_session, longest_session, focus_score}
week_stats: {total_seconds, goal_seconds}
active_session: {id, started_at, project, category} | null
xp_data: {xp_total, level, level_min_xp, next_level_xp, level_title}
challenge: {id, title, type, target_value, xp_reward, completed, progress}
streak_data: {current, longest, week_days: [bool×7], multiplier, shield_count}
habits_today: [{id, name, icon, color, current_streak, done_today}]
exams: [{subject, exam_date, days_remaining, urgency}]
timetable_today: [{title, type, color, start_time, end_time, is_active, project_id}]
daily_plan: {date, tasks: [{text, done}]} | null
heatmap_data: [{date, hours}] (last 30 days)
recent_sessions: [{id, label, project, category, started_at, duration_seconds, type}]
insights: [{type, message}] (latest 3 undismissed)
```

**Layout (vertical stack, gap 12px, padding 16px):**

```
1. Page header row (flex, space-between):
   Left: "Good morning, {name} 👋" (17px, 800, text-primary)
         Greeting changes: morning (<12), afternoon (12-17), evening (17+)
   Right: Date badge ("Thu, May 14", 11px, 500, bg-sidebar, border-default, border-radius 14px)

2. Exam countdown chips (only if exams array not empty)
   Section label: "Upcoming exams"
   Chips row (see §6.7)
   If exam day (urgency='banner'): red full-width banner ABOVE chips row

3. Timetable strip (only if timetable_today not empty)
   Section label: "Today's schedule"
   Strip component (see §6.8)

4. Two-column grid (gap 10px):
   Left:  Focus Ring card (see §6.3 + additional stats)
   Right: Live Timer card (see §6.4) OR "Start a session" CTA card if no active session

5. Daily plan card (see §6.10) — always shown

6. Stats row (3-col, see §6 stat cards):
   TODAY | THIS WEEK | STREAK

7. Two-column grid (gap 10px):
   Left:  XP/Level card (see §6.5)
   Right: Daily Challenge card (see §6.9 spec above — ch-card)

8. Habits today (only if user has active habits)
   Section label: "Habits today"
   Habit rows (see §6.9 — hrow)

9. Quick start row:
   Section label: "Quick start"
   3 buttons: Pomodoro | Analytics | Report

10. Activity heatmap card

11. Recent sessions card (see srow spec)
    Header row: "Recent sessions" (12px, 700) + "See all →" (11px, 600, violet)
    Last 5 sessions, each as srow

12. Insights (if any undismissed):
    Section label: "Insights"
    Insight cards: border-left 2px solid violet, violet/8 bg, 12px italic text
    Dismiss button (ti-x, 14px) on each — calls DELETE /api/analytics/insights/{id}
```

---

### 8.3 Timer Page

**Route:** `/timer`
**Inertia component:** `Pages/Timer.vue`
**Backend:** `SessionController` + session log queries

**Layout: Two-tab interface**

```
Tab bar (below page title): "Active Timer" | "Session Log"
Tab style: 12px, 600, pill tabs (border-radius 20px)
Active tab: violet bg, white text
Inactive tab: bg-card-alt, text-secondary
```

**Active Timer Tab:**

```
Project selector: Searchable dropdown (full-width card)
  Placeholder: "Select project or category..."
  Selected state shows: color dot + project name + category name
  Dropdown items: grouped by category, each with color dot
  "New project" option at bottom

Mode toggle: "Timer" | "Pomodoro" — pill toggle, centered
             Violet fill on active

[TIMER MODE]
Display area (card, text-center):
  Status label: "Ready to start" (text-secondary) OR "Session in progress" (mint)
  Timer: 44px, JetBrains Mono 600, text-primary, format "HH:MM:SS"
  Start/Stop button: 56px tall, full width, border-radius 12px
    Stopped: violet bg, "▶ Start Session" white 14px 600
    Running: red bg (#EF4444), "■ Stop Session" white 14px 600
  
  Session label field (optional, shows when project selected):
    13px input, placeholder "Add a label (optional)..."

[POMODORO MODE]
Display area (card, text-center):
  Pomodoro ring: 200×200px SVG countdown ring (see §5)
  Time remaining: 28px JetBrains Mono 600, center of ring
  Status: "Work time" (mint) | "Break time" (amber)
  Pomodoro dots: 4 dots below ring
  Pause/Resume button + Skip break button
  "Enter Focus Mode" button (outlined, below controls) — navigates to /timer?focus=1

Session notes (always visible, bottom of active tab):
  Textarea: "Add session notes..."
  Saved on session stop automatically

Manual entry (collapsed accordion by default):
  "Add past session +" toggle
  Fields: project, date, start time, end time
  POST /api/sessions (type = 'manual')
```

**Session Log Tab:**

```
Date group headers: sticky, "Today" / "Yesterday" / date string
  12px, 600, text-secondary, padding 8px 0

Session row: flex, align-items center, gap 12px, padding 10px 0
  Left: color dot (8px) + project name (13px, 600) + category chip (9px pill)
  Center: start time (10px mono, text-hint)
  Right: duration (12px mono 600, text-secondary) + type icon (ti-clock/timer/tomato)
  
  Hover: reveals Edit (ti-edit) + Delete (ti-trash) icon buttons
  Edit: opens session edit modal
  Delete: confirm dialog → soft delete

Pagination: "Load more" button at bottom, not infinite scroll
```

---

### 8.4 Focus Mode (Fullscreen Overlay)

**Route:** Overlay within `/timer?focus=1` OR press `F` key from anywhere
**Component:** `Components/Timer/FocusModeOverlay.vue`
**Always dark background** — ignores user's light/dark preference.

```
Background: #0A0A0E, 100vw × 100vh, z-index 9000
Font color: all rgba(255,255,255,X) variants

Header bar (padding 12px 16px, border-bottom rgba(255,255,255,0.06)):
  Left: Subject/project name (12px, 600, rgba(255,255,255,0.7), uppercase, letter-spacing 0.04em)
  Right: "✕ Exit" button (11px, 600, rgba(255,255,255,0.35), border rgba(255,255,255,0.1))

Center content (padding 30px 20px, text-align center):
  Pomodoro position: "Pomodoro {n} of 4" (11px, letter-spacing 0.1em, rgba(255,255,255,0.35), uppercase)
  Timer: 52px, JetBrains Mono 600, white (#fff)
         letter-spacing 0.03em
  Sub-label: "minutes remaining" (12px, rgba(255,255,255,0.4), margin-top 8px)
  Pomodoro progress dots: see §5
  Controls (margin-top 20px, gap 10px, justify-center):
    Pause: violet bg, white text, 40px tall, border-radius 9px, padding 0 20px, "⏸ Pause"
    Skip: rgba(255,255,255,0.07) bg, rgba border, white 60% text, same height, "Skip →"
           Disabled until in break period. Greyed out during work time.

Ambient sounds (below controls, flex center, gap 6px):
  Each: 10.5px, 600, padding 5px 10px, border-radius 7px
        Default: rgba(255,255,255,0.12) border, rgba(255,255,255,0.4) text, transparent bg
        Active:  rgba(124,92,252,0.2) bg, rgba(124,92,252,0.4) border, #A78BFA text
  Options: 🌧 Rain | ☕ Cafe | 🌊 Ocean | 🔕 Silent

Motivational quote (margin 0 16px 16px):
  border-left: 2px solid rgba(124,92,252,0.6)
  padding: 12px 14px
  background: rgba(124,92,252,0.08)
  Font: 12px, italic, rgba(255,255,255,0.55), line-height 1.5
  Changes every 25 minutes

Keyboard shortcuts (shown as tiny hint at very bottom):
  "Space: pause · S: skip break · Esc: exit" — 10px, rgba(255,255,255,0.2)
```

---

### 8.5 Analytics — Daily View

**Route:** `/analytics` (default tab)
**Backend:** `AnalyticsController@daily`

```
Page title: "Analytics" (17px, 800)
Date nav: "<" Yesterday | "Today" | ">" Tomorrow — centered, with date display

Tab bar: "Daily" (active) | "Weekly" | "Monthly"

Card 1 — Focus Score + Overview (flex, ring + stats):
  Focus score ring: 80×80px, same ring component as focus ring but 80px
                    Large number inside: 36px mono 700
                    Color: >=70 = violet, 40-69 = amber, <40 = red
  Stats right of ring: Total time | Sessions | Avg session | Longest session

Card 2 — Hourly breakdown:
  Title: "Hour-by-hour"
  24 vertical bars. Container height: 160px. Each bar: full height container.
  Bar colored by dominant category of that hour.
  Bar height: proportional to minutes logged (max bar = most active hour = 100% height)
  X-axis: hour labels every 3 hours (12am, 3am, 6am... 9pm)
  Hover: tooltip with exact minutes + project name

Card 3 — Sessions list:
  Same as session log tab on Timer page but filtered to selected date
```

---

### 8.6 Analytics — Weekly View

**Route:** `/analytics?tab=weekly`
**Backend:** `AnalyticsController@weekly`

```
Card 1 — Week summary stats (3-col):
  Total hours | vs last week (delta) | Focus score average

Card 2 — Daily line chart (Chart.js):
  Height: 180px
  Two datasets:
    1. Logged hours per day: violet line (#7C5CFC), point radius 4px
    2. Daily goal: dashed amber line (#F5A623), no points
  X-axis: Mon–Sun labels
  Y-axis: hours (0 to max(logged, goal) + 1)
  Chart.js config: responsive true, maintainAspectRatio false, no legend (custom below)
  Custom legend: two items below chart, 10px, color dot + label

Card 3 — Category breakdown (donut):
  Left: 120×120px SVG donut. stroke-width 20. Segments animated on load (stroke-dasharray).
  Right: Legend list. Each item: color square (8×8) + category name + hours + percentage
  No data state: grey full circle + "No sessions this week" text

Card 4 — Best day / Worst day:
  Two stats side by side:
    Best: day name + hours in violet
    Worst: day name + hours in red (only if >1 day logged)
```

---

### 8.7 Analytics — Monthly View

**Route:** `/analytics?tab=monthly`
**Backend:** `AnalyticsController@monthly`

```
Card 1 — 30-day trend (Chart.js area chart):
  Height: 200px
  Dataset 1: daily hours — violet line + 10% opacity area fill below
  Dataset 2: 7-day rolling average — dashed mint line, no area
  X-axis: date labels every 5 days
  Y-axis: hours

Card 2 — Top projects (3 rows):
  Each: color dot (10px) + project name + "Xh Ym" mono text + horizontal bar (background: project color/20, fill project color)
  Bar width: proportional to hours vs top project

Card 3 — Streak calendar:
  Mini calendar grid of current month
  Each day: 24×24px cell, border-radius 4px
  Color: same heatmap intensity scale as dashboard heatmap
  Today: outlined in mint

Card 4 — Insights (if any generated):
  Each insight: border-left 2px violet, violet/8 bg, 12px text
  Dismiss X button on each
```

---

### 8.8 Projects Page

**Route:** `/projects`
**Backend:** `ProjectController@index`

```
Page header: "Projects" + "New project" button (violet, right)

Project grid: 2-column on desktop, 1-column mobile, gap 12px

Each project card (Card component):
  Left accent bar: 3px wide, full height, project color, border-radius 2px 0 0 2px (inside left padding)
  Project name: 14px, 700, text-primary
  Client name (if any): 11px, text-secondary
  Category chip: 9px, category color bg/text, border-radius 10px

  Stats row: Xh logged | Budget: Xh (if set)
             Budget warning: amber bar at 80%, red bar at 100%
  Budget bar: 4px height, border-radius 2px, width 100%
              Background: border-default color
              Fill: green <80%, amber 80-99%, red 100%+

  Sessions count chip: 9.5px, text-hint

  Footer: "Last session X days ago" (10px, text-hint)
  Actions (hover reveal): Edit (ti-edit) | Archive (ti-archive)

New project button → modal (see §9 Modals)
Archived projects: "Show archived" toggle at top. Archived cards shown at 60% opacity.
```

---

### 8.9 Timetable Page

**Route:** `/timetable`
**Backend:** `TimetableController@index`

```
Page header: "Timetable" + "+ Add block" button (violet outline, right)

Week navigation: "← Week" [Mon 12 – Sun 18 May] "Week →"
Today column highlighted: bg rgba(124,92,252,0.04) (very subtle tint)

Grid structure (see §5 for dimensions):
  Time column (44px): 6 AM to 11 PM, label at top of each hour row
                       Font: 9px, JetBrains Mono, text-hint, right-aligned, padding 2px 4px 0 0
  7 day columns: equal width
  Header cells: 10px, 700, text-secondary, text-center, padding 7px 4px
                Today's column header: violet text

Current time indicator:
  Red horizontal line (1px, #EF4444) spanning all day columns (not time column)
  Small red circle (6px) on the left edge of time column at that row
  Only visible for current week

Event blocks (positioned absolute within their grid cell):
  border-radius: 5px
  padding: 3px 5px
  overflow: hidden
  Cursor: pointer (opens edit modal)
  Hover: brightness(1.05)

  Type colors: see timetable strip spec (§6.8)

  Content:
    Block name: 10px, 700, white (or dark contrast depending on bg)
    Time: 8.5px, mono, white 75% opacity

  Conflict state (overlapping blocks):
    Red outline border: 2px solid #EF4444
    Error tooltip on hover: "Time conflict — click to resolve"

Insights card (below grid):
  "Smart insights" section label
  Max 2 insight rows (exam urgency, peak hours, etc.)
  Each: border-left 2px violet, violet/8 bg, 12px text

Add block: clicking empty grid cell → opens create block modal pre-filled with that time slot
```

---

### 8.10 Habits Page

**Route:** `/habits` (or accessible from Goals page as a sub-section)
**Backend:** `HabitController@index` + `HabitController@logs`

```
Page header: "Habit Tracker"
Week nav: same as timetable (shows Mon–Sun of current week)

Stats row (3-col):
  Checks this week (violet) | Active habits (amber) | Longest streak (mint)

Habit grid (full-width card):
  Header row: "Habit" + 7 day abbreviations (M T W T F S S)
  Each habit row: grid (90px + repeat(7, 1fr))
    Name cell: 90px, flex, align-items center, gap 6px
               Emoji icon (14px) + habit name (12px, 600) + streak badge (flame + number, 10px amber mono)
    Day cells: centered check square (22×22px, border-radius 6px)
               Done: habit color bg, check icon white
               Missed: empty, reduced opacity
               Today: interactive, hover shows check icon ghost
               Future: 35% opacity, non-interactive
               Weekend cols (if user has Mon-Fri-only habits): shown at 50% opacity

"+  Add habit" button (dashed border, full width, below grid): opens add habit modal
                                                                 Shown only if < 6 habits

Insights card (below grid):
  Habit-focus correlation insights from InsightService
  If no insights yet: "Check back after 7 days for habit insights"

Per-habit settings (right-click or ti-dots menu on each row):
  Edit name/icon | Set reminder time | Delete habit
```

---

### 8.11 Goals Page (includes Exams)

**Route:** `/goals`
**Backend:** `GoalController@index` + `ExamController@index`

```
Page: Two sections — "Goals" and "Exams"
      Separated by section label + divider

GOALS SECTION:

Goal cards (list, not grid):
  Each card: flex, gap 12px
  Left: goal type icon (ti-target for hours, ti-clock for focus)
  Center: goal title + target (Xh/day or Xh/week)
  Right: progress ring (40×40px, simplified) + percentage text
  Bottom: horizontal progress bar (4px) showing current vs target

  Active goal: standard card
  Hit goal: mint border, "✓ Hit today!" badge in mint

Create goal button: opens modal (type, target, title)
Only 1 daily_hours goal and 1 weekly_hours goal allowed at a time.
Edit/delete via row hover icons.

EXAMS SECTION:

Section label: "Upcoming Exams"
"+ Add exam" button (outline, right)

Exam list (sorted by exam_date ASC):
  Each exam: card row
    Left: urgency indicator (colored left-border, 4px)
    Subject name: 14px, 700
    Date: "May 22" (12px, text-secondary)
    Days remaining chip: same urgency color as exam chip
    Notes (if any): 11px italic text-secondary
    Edit/Delete: hover reveal icons

Past exams (soft-deleted by cron): hidden. "Show past" toggle if any exist.

Empty state: "No upcoming exams. Add one to start counting down."
             + Add exam button centered
```

---

### 8.12 Achievements Page

**Route:** `/achievements`
**Backend:** `GamificationController@badges`

```
Page header: "Achievements"
Stats row (3-col): Badges earned | XP total | Current level

Level progress card (full width):
  Large level display: "Level 4 — Dedicated"
  XP bar (full width, see §6.5)
  "Next: Level 5 (Relentless) at 3,000 XP — you need {delta} more XP"

Badge gallery:
  Grouped by category: Consistency | Volume | Focus | Student | Explorer
  Each group: section label + grid of badges (4-col)

  Each badge cell: text-center, 60×80px min
    Icon: 28px emoji
    Name: 11px, 600, text-primary (earned) / text-hint (locked)
    Locked state: emoji at 30% opacity + lock icon overlay (ti-lock, 12px, text-hint)
    Earned state: amber glow (box-shadow 0 0 8px rgba(245,166,35,0.3)) on hover
    "Earned X days ago": 9px, text-hint, shown on hover tooltip

XP History (bottom, collapsed accordion by default):
  "XP History" label + count badge
  Expanding shows: list of xp_transactions, last 20
  Each: reason (formatted), +{amount} XP (violet), date (text-hint)
```

---

### 8.13 Leaderboard Page

**Route:** `/leaderboard`
**Backend:** `GamificationController@leaderboard`

```
Page header: "Weekly Leaderboard"
Sub-header: "Ranked by XP earned this week (Mon–Sun) · Resets Monday"

Locked state (if Level < 5):
  Center card: lock icon (48px, text-hint) + "Unlock at Level 5 (Relentless)"
               + XP progress bar showing how far to Level 5

Unlocked state:

Opt-in prompt (if not opted in):
  Card: "Join the leaderboard" + alias input + "Join" button
  "Your real name is never shown. Choose any alias."

Leaderboard table (if opted in + data):
  #1-3 podium cards at top (special treatment):
    #1: gold border (amber), large rank badge
    #2: silver (text-hint)
    #3: bronze (#CD7F32 approximated)

  Full list below (rank | alias | XP this week | level badge):
    Your row: highlighted with violet left-border
    Rank: 32px, 700, JetBrains Mono, text-hint
    Alias: 14px, 600
    XP: 13px, mono, violet
    Level badge: 9.5px chip

  "Last updated X min ago" at bottom, text-hint
  Note: "XP shown reflects this ISO week only. Total XP not shown."

Reset countdown: "Resets in X days X hours" — text-secondary, bottom
```

---

### 8.14 Reports Page

**Route:** `/reports`
**Backend:** `ReportController@index`

```
Page header: "Reports" + "Generate Report" button (violet)

Generate Report modal (see §9) — opens on button click

Report list:
  Each report card:
    Title: "{Month Year} Report" or custom title (14px, 700)
    Date range: "May 1–31, 2026" (12px, text-secondary)
    Projects: comma-separated project chips (9.5px, project-color)
    Status badge: "Queued" (amber) | "Generating..." (amber pulse) | "Ready" (mint) | "Failed" (red)
    Actions (if Ready):
      Download button (ti-download, outlined)
      Share button (ti-share, copies share URL to clipboard)
    Delete: ti-trash, hover reveal, confirm dialog

Empty state: "No reports yet. Generate your first time report."

Share link behavior:
  Clicking Share → copies /reports/share/{token} to clipboard → toast "Link copied!"
  Toast: 12px, text-center, bg-card, border-default, border-radius 8px, 2s auto-dismiss
```

---

### 8.15 Settings Page

**Route:** `/settings`
**Backend:** `SettingsController`

```
Layout: two-column on desktop (190px left nav + flex-1 right content)
        Single column on mobile (accordion groups)

Left nav (same style as sidebar nav items):
  Profile | Notifications | Pomodoro | Goals | Leaderboard | Account

RIGHT PANEL — Profile:
  Avatar: 80×80px circle, border border-default
          Upload zone on hover: dark overlay + ti-camera icon, 14px
          Accepted: jpg/png/webp, max 2MB
  Name field (full-width input)
  Timezone selector (searchable select, shows current local time preview)
  Save button (violet, right-aligned)

RIGHT PANEL — Notifications:
  Toggle rows (label + toggle switch on right):
    Push notifications (master toggle — disables all below if off)
    Streak reminder (daily if no session by 8 PM)
    Timetable reminders (2 min before study blocks)
    Habit reminders (at set reminder times)
    Email digest (weekly Monday summary)
  
  Toggle: 40×20px, thumb 14×14px, violet when on, border-default when off

RIGHT PANEL — Pomodoro:
  Three slider rows:
    Work interval: 15–60 min (default 25)
    Short break: 3–15 min (default 5)
    Long break: 10–30 min (default 15)
  Each: label + range slider + current value display (mono)
  Preview: "Your cycle: 25m work → 5m break (×4) → 15m long break"

RIGHT PANEL — Goals:
  Daily goal slider: 1–12h (default 6h)
  Weekly goal: shown as computed (daily × 5) or manual override

RIGHT PANEL — Leaderboard:
  Toggle: "Show me on leaderboard"
  Alias input (shown only if toggle on): max 50 chars
  Note: "Only your alias and XP earned this week are shown."

RIGHT PANEL — Account:
  Export data: "Download all my data (JSON)" button → queues export → shows toast
  Delete account: "Delete Account" (red text button) → confirm dialog (type "DELETE" to confirm)
                  Full deletion within 24h per GDPR
```

---

### 8.16 Auth Pages (Login / Register / Reset)

**Routes:** `/login`, `/register`, `/forgot-password`, `/reset-password`
**Layout:** Centered single card, NO sidebar, NO topbar

```
Background: page background color (#F5F0E8 light / #0C0C10 dark)
Card: 420px width, centered vertically and horizontally, border-radius 14px
      Standard card styling

Logo: centered at top of card (logo-orb 32px + "TimeFlow" 18px 800), margin-bottom 24px

Fields: full-width inputs, 13px, height 40px
        Labels above: 12px, 600, text-secondary, margin-bottom 6px
        Validation errors: 11px, red, margin-top 4px

Login card:
  Email input
  Password input (with show/hide eye icon: ti-eye / ti-eye-off)
  "Forgot password?" link (right-aligned below password, 12px violet)
  "Sign In" button (full-width, violet, height 44px, 14px 600)
  "Don't have an account? Sign up" (centered, 12px text-secondary + violet link)

Register card:
  Full name input
  Email input
  Password input
  Confirm password input
  "Create Account" button
  "Already have an account? Sign in" link

Forgot password card:
  Email input
  "Send reset link" button
  Back to login link

Reset password card:
  New password input
  Confirm new password input
  "Reset Password" button

All auth pages: no animations except standard form validation highlighting.
Errors: Laravel validation errors displayed via Inertia shared errors object.
```

---

### 8.17 Onboarding Wizard

**Route:** `/onboarding` (redirected here after register if `onboarding_completed = false`)
**Component:** `Pages/Onboarding.vue`

```
Background: page background (no sidebar, no topbar)
Layout: centered card, 480px width, border-radius 16px, padding 32px

Progress indicator: 3 dots at top (step indicator)
  Active dot: violet 10px filled circle
  Done dot: violet 6px filled circle
  Pending dot: border-default 6px circle
  Connected by thin violet line (progress line)

STEP 1 — Who are you?
  Heading: "Who best describes you?" (20px, 800)
  Sub: "We'll set up TimeFlow to match how you work." (13px, text-secondary)
  3 role cards (grid 1-col, gap 10px):
    Each: card style, clickable, flex, gap 12px
    Left: icon (32px, role color bg, rounded 8px)
    Right: role name (14px, 700) + description (12px, text-secondary)
    
    Student: ti-school, violet
    Freelancer: ti-briefcase, mint
    Remote Worker: ti-building, sky
    
    Selected: violet border 1.5px + violet/5 bg
  Next button (violet, full-width, "Next →")

STEP 2 — Your daily goal
  Heading: "How many hours do you want to log each day?"
  Slider: 1–12h, step 1, default 6
  Large number display: selected value in 44px mono violet
  Sub: "6 hours = recommended for deep focused work" (12px, text-secondary, updates with value)
  Next button

STEP 3 — Set up your first thing (role-dependent)
  Student:    "Add your first class or study block to your timetable"
              Inline mini timetable block creation form
  Freelancer: "Add your first client project"
              Inline project creation form
  Worker:     "Create your first work category"
              Inline category creation form
  
  "Skip for now" link (text-secondary, below button)
  "Finish setup →" button (violet)

Completion: confetti burst + "You're all set! 🎉" overlay (1.5s) → redirect /dashboard
```

---

## 9. Modals & Overlays

All modals share this base style:

```
Backdrop:   rgba(0,0,0,0.45) (light) / rgba(0,0,0,0.65) (dark)
            Click outside → closes modal (except SessionSummaryModal)
            ESC key → closes modal

Modal box:  bg-card, border border-emphasis, border-radius 14px
            Width: varies by modal (see below)
            Enter: scale 0.95→1 + opacity 0→1, 250ms cubic-bezier(.4,0,.2,1)
            Exit:  scale 1→0.95 + opacity 0, 200ms ease-in
```

### Modal Sizes & Contents

```
Start Session modal:        NOT a modal — inline on Timer page
Session Summary modal:      420px (see §6.11)
Level Up modal:             360px (see §6.12)
Create Project modal:       440px — name, color picker (8 swatches), icon emoji input, client name, budget hours
Create/Edit Block modal:    480px — title, type (4 pill options), color, days (7 checkboxes), start/end time, project link
Create Exam modal:          380px — subject name, date picker, notes textarea
Add Habit modal:            380px — name, emoji picker (grid of 20 emojis), color (6 swatches), reminder time
Generate Report modal:      480px — project multi-select, date range picker (from/to), generate button
Confirm Delete modal:       380px — "Are you sure? This cannot be undone." + Delete (red) + Cancel
Account Delete modal:       380px — "Type DELETE to confirm" input + Delete button (only enabled when input matches)
Edit Session modal:         440px — project, label, date, start time, end time, notes
```

### Toast Notifications

```
Position: top-right, 16px from edge, 16px from top
Stack: multiple toasts stack downward, 8px gap
Width: 300px

Toast card: bg-card, border-default, border-radius 10px, padding 12px 14px
Left colored strip: 3px, border-radius 2px 0 0 2px
  Success: mint
  Error: red
  Info: violet
  Warning: amber

Content: title (13px, 600, text-primary) + optional body (12px, text-secondary)
Close: ti-x 14px, top-right of toast

Auto-dismiss: 3s success/info, 6s error
Animation in: translateX(100%) → 0, 300ms ease-out
Animation out: translateX(120%) + opacity 0, 200ms ease-in
```

---

## 10. Dark Mode

### Implementation

Dark mode is toggled via a class on the root `<html>` element:
```html
<html class="dark">  <!-- dark mode -->
<html>               <!-- light mode (default) -->
```

All color-sensitive CSS uses the `.dark` parent selector pattern OR Tailwind's `dark:` variant.
Transition on all color properties: `transition: background-color 0.35s ease, border-color 0.35s ease, color 0.35s ease;`
Apply on `.card`, `.shell`, `.sidebar`, `.topbar`, `.ni`, `.hc`, `.chk`, `.ttb`.

### User Preference Storage

- Toggle state stored in `localStorage.setItem('theme', 'dark'|'light')`
- Checked on app boot in `app.js` BEFORE Vue mounts (prevents flash of wrong theme)
- System preference (`prefers-color-scheme: dark`) used as default if no localStorage value

### Dark Mode Rendering Rules

```
Focus ring track:       #1F1F28
Focus ring fill:        Same accent colors (no change)
XP bar track:           #1F1F28
Timer bar track:        #1F1F28
Heatmap empty cell:     #1F1F28
Habit circle empty:     #18181F, border rgba(255,255,255,0.10)
Plan checkbox empty:    border rgba(255,255,255,0.20)
Streak dots empty:      rgba(255,255,255,0.10)
Card:                   #13131A, border rgba(255,255,255,0.07)
Sidebar:                #13131A, border-right rgba(255,255,255,0.06)
Topbar:                 #13131A, border-bottom rgba(255,255,255,0.07)
Page bg:                #0C0C10
Input fields:           #18181F bg, border rgba(255,255,255,0.12)
Section labels:         #3E3C4A
```

---

## 11. Responsive & PWA Behavior

### Breakpoints

```
Mobile:   < 768px   — bottom nav, stacked layout, no sidebar
Tablet:   768-1023px — icon-only sidebar (44px wide), compact cards
Desktop:  >= 1024px  — full 190px sidebar, standard layout
Wide:     >= 1440px  — main content max-width 1200px, centered
```

### Mobile Layout Changes

```
Sidebar:       Hidden. Replaced by bottom navigation bar.
Bottom nav:    height 56px, fixed bottom
               5 icons: Dashboard | Timer | Analytics | Timetable | Profile
               Active: violet icon + label. Inactive: text-hint icon, no label.
               Background: bg-sidebar, border-top border-default

Topbar:        Remains. Height 44px. Only logo + avatar (XP chip hidden).

Cards:         Single column. No 2-col or 3-col grids.
               Grid2 → 1 column. Grid3 → scroll row (overflow-x scroll).

Dashboard:     Timetable strip stays horizontal scroll.
               Stat row becomes horizontal scroll (don't stack — too tall).
               Habit circles reduce to 6px diameter on very small screens.

Timer display: 38px on mobile (was 44px).
Focus mode:    Timer: 42px (was 52px). Controls remain the same.
```

### PWA Installed Behavior

```
Display:       standalone (no browser chrome)
Viewport:      full screen, no URL bar
Status bar:    #7C5CFC on iOS (theme_color in manifest)
Splash screen: #F5F0E8 bg + TimeFlow logo centered (light mode)

Install banner (not a modal):
  Fixed bar at top, below topbar
  "Install TimeFlow for the best experience" + "Install" button (violet) + dismiss X
  Shown after 3rd session visit. Not shown if already installed.
  Dismissed: not shown for 7 days.
```

---

## 12. Iconography

TimeFlow uses **Tabler Icons (outline style)** exclusively. The icon webfont is already loaded globally. Never draw custom SVG icon paths.

```
Usage:           <i class="ti ti-{name}" aria-hidden="true"></i>
Sizing:          font-size property (14px standard, 16px emphasis, 20px large)
Color:           inherits from parent — controlled by text color utility classes
Interactive:     Add aria-label to parent button if icon-only
Never use:       -filled suffix variants (not loaded), custom SVG paths, image-based icons
```

### Icon Assignments

```
Dashboard:        ti-layout-dashboard
Timer:            ti-player-play (start) / ti-player-stop (stop) / ti-clock (session log)
Analytics:        ti-chart-bar
Projects:         ti-folder
Achievements:     ti-trophy
Goals:            ti-target
Leaderboard:      ti-podium
Reports:          ti-file-analytics
Settings:         ti-settings
XP / Lightning:   ti-bolt
Notifications:    ti-bell
User / Avatar:    ti-user
Check:            ti-check
Close:            ti-x
Add:              ti-plus
Edit:             ti-edit
Delete:           ti-trash
Download:         ti-download
Share:            ti-share
Eye (show/hide):  ti-eye / ti-eye-off
Lock:             ti-lock
School/Class:     ti-school
Alert:            ti-alert-circle / ti-alert-triangle
Arrow:            ti-arrow-up / ti-arrow-down / ti-arrow-right / ti-arrow-left
Chevron:          ti-chevron-down / ti-chevron-up
Calendar:         ti-calendar
Clock:            ti-clock
Streak shield:    ti-shield
Pomodoro:         ti-tomato (if available, else ti-circle)
Focus mode:       ti-maximize
Timetable:        ti-table
Habits:           ti-checkbox
Exam:             ti-notes
Plan tasks:       ti-list-check
```

---

## 13. Empty States

Every feature that can have no data must have a designed empty state. Never show a blank white box.

```
Page/Component          Empty Illustration           Primary CTA
------------------      --------------------         -----------
Dashboard (no sessions) Soft grey clock icon (48px)  "Start your first session" → Timer page
Dashboard (no habits)   Checkbox icon (48px)          "Add habits to track" → Habits page
Timer (no projects)     Folder icon (48px)            "Create your first project" → modal
Timetable (empty)       Calendar icon (48px)          "Add your first time block" → modal
Habits (none)           Checkbox icon (48px)          "Add up to 6 habits" → modal
Goals (none)            Target icon (48px)            "Set your first goal" → modal
Exams (none)            Notes icon (48px)             "Add an upcoming exam" → modal
Leaderboard (locked)    Lock icon (48px)              Shows level progress to unlock
Reports (none)          File icon (48px)              "Generate your first report" → modal
Achievements (0 badges) Trophy icon (48px)            "Start tracking to earn badges"
Analytics (no data)     Chart icon (48px)             "Log some sessions to see analytics"
Session log (no sessions) Clock icon (48px)           "Start a session to begin logging"
```

Empty state visual spec:
```
Container: flex column, align-items center, justify-content center, padding 32px, gap 12px
Icon:      48px, text-hint color (30% opacity)
Heading:   14px, 600, text-secondary
Sub text:  12px, text-hint (optional)
CTA:       Outlined violet button, 13px, 600, height 36px
```

---

## 14. Backend Data Mapping

This section documents exactly which backend API call populates which UI component. Use this to verify the frontend-backend contract.

```
UI Component                   API Call                         Response fields used
------------                   --------                         -------------------
Focus Ring                     GET /api/analytics/today         total_seconds, focus_score
                                (or Inertia prop: today_stats)
Live Timer display             Inertia prop: active_session     started_at, project.name, project.color
Stop button                    POST /api/sessions/{id}/stop     meta.xp_gained, meta.new_level, meta.badges_earned
Session Summary Modal          Response from stop               session, meta (all fields)
XP Bar + Level                 Inertia prop: xp_data            xp_total, level, level_min_xp, next_level_xp, level_title
Streak widget                  Inertia prop: streak_data        current, longest, week_days, multiplier, shield_count
Streak week dots               streak_data.week_days            boolean[7] Mon-Sun
Daily challenge card           Inertia prop: challenge          title, completed, progress, xp_reward
Exam chips                     Inertia prop: exams              subject, days_remaining, urgency
Timetable strip (dashboard)    Inertia prop: timetable_today    title, type, color, start_time, end_time, is_active
Today's plan                   Inertia prop: daily_plan         tasks[{text, done}]
Plan task check                PATCH /daily-plans/{date}/task/{i}  {done: true} → response: {all_done, xp_gained}
Habit row circles              Inertia prop: habits_today       id, name, icon, color, current_streak, done_today
Habit circle tap               POST /api/habits/{id}/log        {date, done} → {all_done}
Stats: Today                   Inertia prop: today_stats        total_seconds (convert to hours)
Stats: This week               Inertia prop: week_stats         total_seconds, goal_seconds
Stats: Streak                  Inertia prop: streak_data        current, multiplier
Activity heatmap               Inertia prop: heatmap_data       [{date, hours}] — 30 days
Recent sessions                Inertia prop: recent_sessions    label, project, category, started_at, duration_seconds
Insights card (dashboard)      Inertia prop: insights           [{type, message}]
Insights dismiss               DELETE /api/analytics/insights/{id}
Analytics daily chart          GET /api/analytics/today         hourly breakdown
Analytics weekly               GET /api/analytics/weekly        daily_hours[], category_breakdown[], best_day
Analytics monthly              GET /api/analytics/monthly       trend_data[], top_projects[], streak_calendar
Timetable grid                 GET /api/timetable               all blocks + is_today bool
Auto-timer start               Push notification → deep link    /timer?project={id}&autostart=1
                               Vue Timer.vue onMounted: reads query params → calls POST /api/sessions/start
Pomodoro complete              POST /api/sessions/pomodoro/complete  → returns meta (XP, badges)
Leaderboard                    GET /api/gamification/leaderboard     [{alias, xp_this_week, level, rank}]
Reports list                   GET /api/reports                 [{id, title, date_from, date_to, status, share_token}]
Report download                GET /api/reports/{id}/download   redirects to S3 signed URL
Report share                   Copies /reports/share/{token} to clipboard
Settings save profile          PATCH /api/profile               {name, timezone}
Settings save notifications    PATCH /api/settings/notifications  all toggle values
Settings pomodoro              PATCH /api/settings/pomodoro     {work_min, break_min, long_min}
```

### Inertia Props Strategy

`DashboardController@index` passes ALL dashboard data in a single Inertia response. No client-side fetching on the dashboard — everything is server-rendered into props.

For subsequent updates (habit tap, plan check, timer stop): use Axios calls to API endpoints. Update Pinia store and re-render relevant components reactively.

Pinia stores:
```
activeSession.js:   { sessionId, startedAt, project, category } | null
user.js:            { id, name, level, xpTotal, streakCurrent, streakLongest, dailyGoalHours }
notifications.js:   { queue: [{id, type, title, body, autoDismiss}] }
```

---

## 15. Tailwind Config Reference

Add to `tailwind.config.js`:

```javascript
module.exports = {
  darkMode: 'class',
  content: ['./resources/**/*.{vue,js,ts,blade.php}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'Cascadia Code', 'monospace'],
      },
      colors: {
        // Light mode page background
        parchment: {
          DEFAULT: '#F5F0E8',
          dark:    '#F0EAE0',
          alt:     '#FAF7F2',
        },
        // Accent system
        violet: { DEFAULT: '#7C5CFC', light: '#A78BFA', dark: '#5B3FD4' },
        mint:   { DEFAULT: '#0ECFA4', light: '#0ECFA4', dark: '#0A8A6C' },
        amber:  { DEFAULT: '#F5A623', light: '#F5A623', dark: '#92400E' },
        rose:   { DEFAULT: '#F06292', light: '#F06292', dark: '#BE185D' },
        sky:    { DEFAULT: '#38BDF8', light: '#38BDF8', dark: '#0369A1' },
      },
      borderRadius: {
        card:   '11px',
        chip:   '20px',
        button: '9px',
      },
      fontSize: {
        'section-label': ['9.5px', { fontWeight: '700', letterSpacing: '0.09em' }],
        'stat-lg':       ['22px',  { fontFamily: 'JetBrains Mono', fontWeight: '700' }],
        'timer':         ['28px',  { fontFamily: 'JetBrains Mono', fontWeight: '600' }],
        'timer-xl':      ['52px',  { fontFamily: 'JetBrains Mono', fontWeight: '600' }],
      },
    },
  },
  plugins: [],
}
```

### Key Tailwind Utilities in Use

```
Layout:     grid grid-cols-[190px_1fr]  grid-rows-[48px_1fr]
            flex items-center justify-between gap-{n}
Cards:      bg-white dark:bg-[#13131A] border border-[rgba(80,60,20,0.10)] dark:border-[rgba(255,255,255,0.07)]
            rounded-[11px] p-[13px_15px]
Typography: font-sans font-mono text-[#1C1917] dark:text-[#EEEAF4]
            text-[12.5px] font-medium
Accent:     text-violet-DEFAULT bg-violet-DEFAULT/12 border-violet-DEFAULT/22
Animation:  transition-all duration-[350ms] ease-out
            animate-[fadeUp_0.5s_ease_both]
Scrollbar:  [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]
```

---

*Document Owner: Design Lead*
*Version: 1.0.0*
*Last Updated: May 2026*
*Matches PRD: v2.0.0*
*Next Review: Before Phase 2 kickoff (after Phase 1 foundation complete)*
