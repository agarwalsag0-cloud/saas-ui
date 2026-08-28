---
name: Core Ledger
colors:
  surface: '#f9f9ff'
  surface-dim: '#d3daea'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f3ff'
  surface-container: '#e7eefe'
  surface-container-high: '#e2e8f8'
  surface-container-highest: '#dce2f3'
  on-surface: '#151c27'
  on-surface-variant: '#464555'
  inverse-surface: '#2a313d'
  inverse-on-surface: '#ebf1ff'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4e44e2'
  primary: '#3e32d3'
  on-primary: '#ffffff'
  primary-container: '#5850ec'
  on-primary-container: '#e9e5ff'
  inverse-primary: '#c3c0ff'
  secondary: '#575e70'
  on-secondary: '#ffffff'
  secondary-container: '#d9dff5'
  on-secondary-container: '#5c6274'
  tertiary: '#4e5152'
  on-tertiary: '#ffffff'
  tertiary-container: '#67696a'
  on-tertiary-container: '#e8e9ea'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3424ca'
  secondary-fixed: '#dce2f7'
  secondary-fixed-dim: '#c0c6db'
  on-secondary-fixed: '#141b2b'
  on-secondary-fixed-variant: '#404758'
  tertiary-fixed: '#e1e3e4'
  tertiary-fixed-dim: '#c5c7c8'
  on-tertiary-fixed: '#191c1d'
  on-tertiary-fixed-variant: '#454748'
  background: '#f9f9ff'
  on-background: '#151c27'
  surface-variant: '#dce2f3'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 60px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-lg:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  3xl: 64px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 32px
  max-width: 1440px
---

## Brand & Style

The design system is anchored in a **Corporate / Modern** aesthetic, optimized for high-utility SaaS environments. It balances executive-level sophistication with the dense information requirements of multi-business management. The visual language is defined by precision, clarity, and a "quiet premium" feel—avoiding unnecessary ornamentation in favor of crisp execution.

The brand personality is authoritative yet enabling. It uses a high-contrast foundation (deep slates against stark whites) to establish a clear hierarchy of information. Movement between public-facing marketing pages and deep administrative tables is unified through shared geometry and a consistent "Electric Indigo" primary anchor. 

**Key Visual Principles:**
- **Intentional Density:** Information is packed efficiently in admin views using tight vertical rhythm, while public views utilize generous white space to guide the narrative.
- **Precision Engineering:** 1px borders and disciplined alignment reflect the platform's reliability.
- **Modern Minimalist:** Every element serves a functional purpose; decorative elements are restricted to subtle gradients or micro-interactions.

## Colors

The palette is built on a "Slate & Indigo" foundation. The primary **Electric Indigo** (#5850EC) provides a vibrant, high-energy focal point for actions and active states, while the neutral scale uses a blue-tinted gray (Slate) to maintain a cool, professional temperature.

- **Primary (Electric Indigo):** Used for primary buttons, active navigation states, and progress indicators.
- **Neutrals:** A comprehensive scale from White (#FFFFFF) to Slate-900 (#111827). Backgrounds primarily use Gray-50 (#F9FAFB) for subtle contrast against pure white cards.
- **Semantics:** High-saturation tones for functional feedback. For "Pending" states, use a desaturated blue-gray to indicate a "paused" but healthy status. 
- **Application:** Use subtle background tints (e.g., Success-50) behind semantic text to create "Lo-Fi" badges that don't compete with the primary call-to-action.

## Typography

This design system employs a dual-font strategy. **Plus Jakarta Sans** is used for Display and Headline roles to provide a modern, slightly rounded, and approachable "premium" character. **Inter** is utilized for all functional UI text, body copy, and labels, chosen for its exceptional legibility in data-dense environments.

- **Hierarchy:** Use `display-lg` exclusively for marketing hero sections. Admin dashboards should lead with `headline-sm` or `title-lg`.
- **Labels:** `label-sm` uses uppercase with increased letter spacing for secondary metadata (e.g., table headers, section subtitles).
- **Weight:** Maintain a strict 400/500/600 weight distribution. Avoid 700+ weights in the body to keep the UI from feeling "heavy."

## Layout & Spacing

The system uses an **8px linear scale** (with a 4px step for micro-adjustments). This ensures mathematical harmony across all components.

- **Grid:** A 12-column fluid grid is used for marketing and public pages. For Admin/Business portals, use a **Sidebar + Content** model where the content area utilizes a flexible flexbox grid with fixed 24px gutters.
- **Density Toggles:**
    - **Public:** Use `lg` and `xl` spacing for sections to create an "airy" feel.
    - **Admin/Business:** Use `sm` and `md` spacing to maximize data visibility on a single screen.
- **Sidebar:** Fixed width of 260px for desktop. It collapses to a 64px icon-only rail or hides entirely behind a hamburger menu on mobile.

## Elevation & Depth

Hierarchy is established through **Tonal Layering** and **Subtle Ambient Shadows**. We avoid heavy shadows to maintain the "Modern Minimal" feel.

- **Layer 0 (Canvas):** Gray-50 (#F9FAFB). Used as the primary background for all application views.
- **Layer 1 (Card/Surface):** White (#FFFFFF). All primary content containers. Use a 1px solid border (Gray-200) and a "Soft" shadow: `0px 1px 3px rgba(0,0,0,0.1), 0px 1px 2px rgba(0,0,0,0.06)`.
- **Layer 2 (Overlays/Dropdowns):** White (#FFFFFF). Use a more pronounced "Ambient" shadow: `0px 10px 15px -3px rgba(0,0,0,0.1), 0px 4px 6px -2px rgba(0,0,0,0.05)`.
- **Active State:** Use a 2px Electric Indigo border for focused inputs or active selections, rather than increasing shadow depth.

## Shapes

The design system uses a **Rounded** (Level 2) shape language to soften the corporate edge of the SaaS platform.

- **Standard Elements:** 0.5rem (8px) for buttons, input fields, and small components.
- **Large Components:** 1rem (16px) for cards, modals, and primary containers.
- **Pill Elements:** Badges and status indicators use a full `rounded-full` (999px) radius to distinguish them from interactive buttons.
- **Consistency:** 1px solid borders are mandatory for all non-pill elements to provide structural definition against the Gray-50 background.

## Components

### Buttons
- **Primary:** Electric Indigo background, White text. High-contrast.
- **Secondary:** White background, Gray-200 border, Gray-900 text.
- **Ghost:** No background or border. Indigo text for actions, Gray text for navigation.
- **Icon Buttons:** 40x40px touch target, centered 20px icon. 

### Inputs & Forms
- **Text/Search Inputs:** 1px Gray-300 border, 12px horizontal padding. On focus, the border transitions to Electric Indigo with a 3px soft indigo "halo" (shadow).
- **Toggles:** Use a 20px circular thumb. When "On," the track is Electric Indigo; when "Off," it is Gray-200.
- **Checkboxes:** Square with a 4px corner radius. On selection, fill with Electric Indigo and a white checkmark.

### Feedback
- **Badges:** Pill-shaped. Use a "Soft Tint" style: e.g., Success is a 10% opacity green background with 100% opacity green text.
- **Status Indicators:** A 6px solid circle preceding text (e.g., `(•) Active`).
- **Toasts:** Positioned top-right. White background, thick 4px left-border colored by status (Success/Error).

### Navigation
- **Sidebars:** Use a dark theme (Slate-900) for Admin/Business portals to create strong visual separation from the content. Navigation items should have a 4px left-edge indicator when active.
- **Navbars:** Pure white with a 1px bottom border. Links use `body-md` with `label-md` weight.