# Cards: Layout Builder

The **Cards** element creates a flexible card grid with image/video, text, badge, link and optional animations.

## 1. Global Block Settings

You can find these settings via the **Global Block Settings** button.

### Grid

- **Columns (Desktop / Tablet / Mobile)**: Controls how many cards are shown per row.
- **Gap between Cards**: `collapse` (no gap), `column-collapse` (no left/right gap), `small`, `medium`, `large`.
- **Equal height for all cards**: Useful when text lengths vary.

### Card Style

- **Card Color**: Global style for all cards.
- **Card Padding**: `small`, `default`, `large`.
- **Shadow**: Global shadow style.

### Section

- **Section Background**: Background color for the whole block.
- **Section Background (Image/Video)**: Optional background media for the block.
- **Section Padding**: Vertical/inner spacing for the whole section.
- **Container Width**: Limited or full width.

### Animations (UIkit)

- **Enable animations**: Enables animations globally.
- **Enable ScrollSpy**: Animation starts when cards enter the viewport.
- **Animation delay (ms)**: Delay between card animations.
- **Repeat animations**: Restart animation when cards become visible again.
- **Cascading delay**: Each following card starts with an additional delay.

## 2. Editing Cards

In the **Cards** repeater you create individual cards.

### Fields visible directly per card

- **Layout**: `media-top`, `media-bottom`, `media-left`, `media-right`, `media-overlay`.
- **Card Color**: Optional override compared to the global **Card Color**.
- **Hint for empty value**: *Inherited (Global setting)*.
- **Animation**: Card-specific animation.
- **Image or Video**: Media file from the media pool.
- **Title / Subtitle / Text**: Main card content.

## 3. Modal: Media Settings

Open **Media Settings** per card to configure image/video details:

- **Alt Text** and **Decorative Image**
- **Caption**
- **Media Width** (for left/right layout)
- **Aspect Ratio**
- **Lightbox**
- **Cover Mode**
- **Video Display** and **Video Controls**

Note: If full-card linking is enabled, the image should usually be marked as decorative.

## 4. Modal: Layout Settings

Configure card-specific layout details here:

- **Width Mobile / Tablet / Desktop**
- **Badge** + **Badge Color**
- **Vertical Alignment** for horizontal layouts
- **Shadow (override)**

Important: In the repeater, this modal appears directly after the **Layout** field.

## 5. Modal: Linking

You can configure a link for each card:

- **Link Type**: No link, External URL or Internal page
- **External URL** or **Internal page** (depending on link type)
- **Link Text**
- **Button Style**
- **Button Alignment**
- **Link entire card**

Important: In the repeater, this modal appears directly after the **Card Color** field.

## 6. Optional Modal: Extras

If additional fields are provided in your installation via `CardsRepeaterExtra`, an extra **Extras** modal is shown per card.

- This modal is optional and only visible when extra fields are configured.
- Typical content: project-specific additional options.

Recommendation:

- For teaser-like cards, **Link entire card** is often the best UX.
- For editorial flexibility, a classic button can be used instead.

## 7. Practical Recommendations

- Use consistent media formats and enable **Equal height for all cards** for calm, aligned layouts.
- With many cards, prefer small to medium gaps.
- Use animations sparingly and avoid excessive delays.
- On mobile devices, **1 column** is usually best.
