---
name: Accademia Administrative Presence
colors:
  surface: '#fcf9f8'
  surface-dim: '#dcd9d9'
  surface-bright: '#fcf9f8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3f2'
  surface-container: '#f0eded'
  surface-container-high: '#eae7e7'
  surface-container-highest: '#e5e2e1'
  on-surface: '#1b1c1c'
  on-surface-variant: '#44474d'
  inverse-surface: '#303030'
  inverse-on-surface: '#f3f0ef'
  outline: '#75777e'
  outline-variant: '#c5c6ce'
  surface-tint: '#4f5f7b'
  primary: '#04162e'
  on-primary: '#ffffff'
  primary-container: '#1a2b44'
  on-primary-container: '#8292b0'
  inverse-primary: '#b6c7e7'
  secondary: '#735c00'
  on-secondary: '#ffffff'
  secondary-container: '#fed65b'
  on-secondary-container: '#745c00'
  tertiary: '#1d1503'
  on-tertiary: '#ffffff'
  tertiary-container: '#332913'
  on-tertiary-container: '#9f9073'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d5e3ff'
  primary-fixed-dim: '#b6c7e7'
  on-primary-fixed: '#091c34'
  on-primary-fixed-variant: '#374762'
  secondary-fixed: '#ffe088'
  secondary-fixed-dim: '#e9c349'
  on-secondary-fixed: '#241a00'
  on-secondary-fixed-variant: '#574500'
  tertiary-fixed: '#f3e0c0'
  tertiary-fixed-dim: '#d6c4a5'
  on-tertiary-fixed: '#231a06'
  on-tertiary-fixed-variant: '#51452d'
  background: '#fcf9f8'
  on-background: '#1b1c1c'
  surface-variant: '#e5e2e1'
  surface-ivory: '#FCFBF7'
  border-subtle: '#E5E5E5'
  heritage-red: '#E61F19'
  graphite-text: '#222222'
typography:
  display-lg:
    fontFamily: Oswald
    fontSize: 40px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  headline-md:
    fontFamily: Oswald
    fontSize: 24px
    fontWeight: '500'
    lineHeight: '1.3'
    letterSpacing: 0.03em
  headline-sm:
    fontFamily: Oswald
    fontSize: 18px
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: 0.03em
  body-lg:
    fontFamily: Work Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Work Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  body-sm:
    fontFamily: Work Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
  label-caps:
    fontFamily: Work Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.08em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  sidebar-width: 280px
  container-max-width: 1440px
  gutter: 24px
  margin-page: 40px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

This design system establishes a premium, institutional environment for the Accademia Ucraina di Balletto. It moves away from the generic aesthetics of modern SaaS platforms, instead embracing the gravitas of a historic cultural institution. The visual language is grounded in the physical reality of the ballet studio: the warmth of wood floors, the precision of the barre, and the authoritative calm of a prestigious academy.

The design style is **Corporate / Modern** with a **Minimalist** focus on high-quality materials. It prioritizes clarity and dignity over digital trends. The interface serves as a silent partner to the administrative staff—functional, stable, and undeniably official. Every element is designed to evoke a sense of order and professional pride, ensuring that the software feels like a digital extension of the Academy’s physical halls.

## Colors

The palette is derived from the prestige of ballet uniforms and the warmth of the studio environment. 

- **Primary (Deep Navy):** Used for navigation backgrounds and primary actions. It represents the "Uniform"—authoritative and grounding.
- **Secondary (Soft Gold):** Reserved for high-value accents, active states, or subtle decorative elements that signify quality.
- **Tertiary (Warm Beige):** Used for subtle section headers or alternative background fills to prevent the interface from feeling cold or clinical.
- **Surface (Ivory):** The main background color, providing a softer, more sophisticated reading experience than pure white.
- **Status Colors:** Use the Heritage Red sparingly for critical alerts, maintaining the institutional calm of the overall system.

## Typography

The typographic strategy balances the institutional weight of **Oswald** with the high legibility of **Work Sans**.

- **Headlines:** Use Oswald for all headings. The condensed nature and uppercase styling mimic official certificates and classical theatre posters. Ensure generous tracking (letter spacing) to maintain an "expensive" feel.
- **Body Text:** Work Sans provides a neutral, highly readable foundation for complex administrative tasks.
- **Scale:** On mobile devices, `display-lg` should scale down to 32px to ensure readability without excessive wrapping.
- **Hierarchy:** Use the `label-caps` style for table headers and metadata to create a clear distinction from primary content.

## Layout & Spacing

This design system utilizes a **Fixed Grid** layout for desktop to maintain a composed, editorial feel. 

- **Navigation:** A permanent left-hand sidebar (Deep Navy) acts as the anchor of the workplace. It should contain the Academy logo at the top and clearly segmented navigation links.
- **Main Content:** Centered with a maximum width of 1440px. Use wide margins (40px+) to create a sense of "breathing room," avoiding the cluttered look of typical spreadsheets.
- **Grid:** A 12-column system for internal card layouts, using a 24px gutter.
- **Responsive Behavior:** On tablet, the sidebar may collapse into an icon-only rail or a drawer. On mobile, the layout shifts to a single-column fluid flow with 16px horizontal margins.

## Elevation & Depth

To maintain a prestigious and grounded aesthetic, avoid heavy drop shadows or floating effects. 

- **Tonal Layering:** Depth is primarily communicated through color. The Ivory background holds cards that are pure White.
- **Low-Contrast Outlines:** Use 1px borders in Light Grey (#E5E5E5) for cards and input fields. This creates a "blueprint" precision that feels structured and reliable.
- **Subtle Depth:** A single, extremely soft ambient shadow (0px 4px 20px rgba(0,0,0,0.04)) may be used on the primary "Active" card to gently lift it from the background without breaking the flat, institutional aesthetic.

## Shapes

The shape language is disciplined and conservative. We use **Soft (0.25rem)** corners to take the edge off the "industrial" feel of the software while remaining formal. 

- **Buttons & Inputs:** Use the standard 0.25rem radius.
- **Cards:** May use up to 0.5rem (`rounded-lg`) to provide a distinct container feel for large data sets.
- **Avoid:** Full circles or pill shapes (except for very small status dots), as they feel too "playful" for an institutional workplace.

## Components

- **Buttons:** Primary buttons are Deep Navy with White text. Secondary buttons use a Navy outline or the Warm Beige background. Labels are always in Work Sans, Semi-bold.
- **Cards:** The primary vehicle for information. Cards must have a 1px Light Grey border, a White background, and generous internal padding (min 32px).
- **Navigation Links:** High-contrast text on the Navy sidebar. Active states are indicated by a Soft Gold vertical bar on the left edge or a subtle Gold text color change.
- **Input Fields:** Professional and sturdy. Backgrounds should be White with a 1px border that thickens slightly on focus. Avoid "floating labels"; use clear, top-aligned labels in `label-caps` style.
- **Data Tables:** Clean rows with dividers in Light Grey (#E5E5E5). No alternating row colors; instead, use a subtle hover state in Surface Ivory.
- **Icons:** Use thin-line, elegant icons (2px stroke). Avoid filled or "bubbly" icon sets.