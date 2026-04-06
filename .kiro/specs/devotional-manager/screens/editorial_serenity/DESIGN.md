# Design System Document: High-End Editorial Experience

## 1. Overview & Creative North Star
**Creative North Star: The Digital Curator**

This design system is built to transcend the "template" aesthetic of modern SaaS. It is rooted in the philosophy of high-end editorial print—think boutique journals and architectural monographs. The goal is to move away from rigid, boxy layouts toward a fluid, layered experience that prioritizes composure and "The Digital Curator" mindset.

We achieve this through **Intentional Asymmetry** and **Tonal Depth**. By breaking the expected 12-column grid with oversized serif typography and overlapping surface elements, we create a visual rhythm that feels bespoke. This is not just a UI; it is an environment that feels serene, professional, and authoritative.

---

## 2. Colors
Our palette moves away from sterile whites and harsh blacks, favoring a "Living Neutral" foundation of parchment and bone.

### Palette Strategy
*   **The "No-Line" Rule:** 1px solid borders for sectioning are strictly prohibited. Boundaries must be defined solely through background color shifts. For example, a `surface-container-low` section should sit on a `surface` background to define its edges.
*   **Surface Hierarchy & Nesting:** Treat the UI as a series of physical layers—stacked sheets of fine paper. Use `surface-container` tiers (Lowest to Highest) to define importance. An inner card should use a higher tier (e.g., `surface-container-highest`) than the section it sits within.
*   **The "Glass & Gradient" Rule:** Use Glassmorphism for floating navigation and overlays. Use `surface` or `surface-variant` colors at 70-80% opacity with a `backdrop-blur` (12px-20px). 
*   **Signature Textures:** For primary CTAs or Hero backgrounds, use subtle linear gradients transitioning from `primary` (#000000) to `primary-container` (#101C30). This adds a "soul" to the darkness that flat hex codes cannot achieve.

**Core Tokens:**
*   **Background:** `#FCF9F2` (Parchment)
*   **Primary:** `#000000` (Deep Slate/Black)
*   **Secondary:** `#56642B` (Moss Green - used for quiet emphasis)
*   **Surface Container:** `#F1EEE7` (Bone)

---

## 3. Typography
The typography system relies on a "High-Contrast Tension" between the intellectual heritage of a serif and the functional clarity of a sans-serif.

*   **Display & Headlines (Newsreader):** Use these for all storytelling and high-level headings. The variable weight of *Newsreader* should be utilized to create a sense of importance. Large `display-lg` headings should often have tight letter-spacing to feel like a masthead.
*   **UI & Body (Inter):** Reserved for functional data, long-form reading, and interface elements. *Inter* provides the modern, "tech-adjacent" balance to the traditional serif.
*   **Editorial Hierarchy:** Always pair a `headline-lg` (Serif) with a quiet `label-md` (Sans-serif, Uppercase) positioned above it to act as an eyebrow tag. This creates an immediate editorial feel.

---

## 4. Elevation & Depth
In this system, depth is organic, not artificial. We use light and layering rather than structural lines.

*   **The Layering Principle:** Stacking tiers (e.g., `surface-container-lowest` on `surface-container-low`) creates a soft, natural lift. This is the preferred method for defining cards and modules.
*   **Ambient Shadows:** If an element must "float" (like a modal or dropdown), use extra-diffused shadows.
    *   *Blur:* 40px - 60px.
    *   *Opacity:* 4% - 6%.
    *   *Color:* Use a tinted shadow (derived from `on-surface`) rather than pure black.
*   **The "Ghost Border" Fallback:** If a border is required for accessibility, use the `outline-variant` token at 15% opacity. Never use 100% opaque borders.
*   **Glassmorphism:** Apply to navigation bars to allow the "parchment" background to bleed through as the user scrolls, maintaining a sense of place and material continuity.

---

## 5. Components

### Buttons
*   **Primary:** `primary` background with `on-primary` text. Use `lg` (1rem) rounded corners. Padding: `12px 24px`.
*   **Secondary:** `surface-container-highest` background. No border. Text in `on-surface`.
*   **Tertiary:** Ghost style. No background. Underline only on hover to maintain the "clean" editorial look.

### Cards & Modules
*   **Rule:** Forbid divider lines.
*   **Implementation:** Use vertical whitespace (32px, 48px, or 64px) from the spacing scale to separate content. For nested content, use a background shift to `surface-container-low`.

### Input Fields
*   **Style:** Minimalist. No four-sided boxes. Use a bottom-only "Ghost Border" or a subtle `surface-container-high` fill. 
*   **Focus State:** Transition the background to `surface-container-highest` with a subtle 1px `primary` bottom border.

### Chips
*   **Action Chips:** Rounded `full` (9999px). Use `secondary-container` for a subtle moss-green wash that signals interactivity without the aggression of a standard "brand" color.

### Editorial Signature Components (Additions)
*   **The Pull-Quote:** A `headline-md` serif text block, center-aligned, with `secondary` (#56642B) quotation marks. 
*   **The Floating Nav:** A glassmorphic bar using `surface` at 80% opacity, `backdrop-blur`, and an `outline-variant` @ 10% opacity.

---

## 6. Do's and Don'ts

### Do
*   **Do** use "Generous Breathing Room." If you think there is enough margin, add 16px more.
*   **Do** lean into the "Moss" (`secondary`) color for success states or subtle highlights; it is more sophisticated than standard green.
*   **Do** use asymmetrical layouts (e.g., 60% width text column offset to the right) for landing pages.

### Don't
*   **Don't** use 1px solid black borders. It breaks the "Serenity" of the parchment background.
*   **Don't** use pure white (#FFFFFF) for backgrounds unless it is the `surface-container-lowest` for a specific floating card.
*   **Don't** use "Inter" for large hero headings. It robs the system of its editorial character.
*   **Don't** use heavy shadows. If the element looks like it's "hovering" more than 2mm off the page, the shadow is too dark.