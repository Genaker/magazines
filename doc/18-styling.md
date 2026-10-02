# Styling (Tailwind CSS + SCSS)

## Overview

Magazines uses a **hybrid** frontend styling setup:

| Layer | Technology | Role |
|-------|------------|------|
| Utilities | **Tailwind CSS v4** | Layout, spacing, colors, typography in Blade (`class="..."`) |
| Custom styles | **SCSS** | Component-specific CSS that is awkward in utilities |
| Bundler | **Vite** + `@tailwindcss/vite` | Compiles and hashes assets into `public/build/` |
| Forms | `@tailwindcss/forms` | Sensible default styles for inputs and selects |
| Interactivity | **Alpine.js** | Show/hide UI; `[x-cloak]` hides elements until Alpine loads |

**Font:** Figtree (loaded from Bunny Fonts in layouts).

## File layout

```
resources/css/
├── app.css              # Tailwind entry (Vite input) — do not rename to .scss
├── custom.scss          # Global custom SCSS (imported from app.css)
└── post-gallery.scss    # Post gallery grid (imported from gallery.js)

resources/js/
└── gallery.js           # Imports post-gallery.scss on post pages

public/build/            # Production output (manifest + hashed CSS/JS)
```

Layouts load the main bundle:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Post pages additionally load the gallery bundle:

```blade
@vite('resources/js/gallery.js')
```

## Tailwind entry (`app.css`)

Tailwind v4 is configured in CSS, not `tailwind.config.js`:

```css
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.js';
/* …pagination views, compiled Blade cache… */

@plugin '@tailwindcss/forms';

@theme {
    --font-sans: 'Figtree', ui-sans-serif, system-ui, sans-serif;
}

@import './custom.scss';
```

- **`@source`** — tells Tailwind which files to scan for utility class names.
- **`@theme`** — design tokens (here: sans font family).
- **`@plugin`** — enables the forms plugin.

### Why the entry must stay `.css`

Tailwind v4’s Vite plugin expects a **CSS** entry. If you make `app.scss` the Vite input, Sass runs first and leaves `@source`, `@plugin`, and `@theme` unprocessed — utility classes like `flex` and `bg-gray-100` will **not** be generated and the site will look unstyled.

**Rule:** keep Tailwind directives in `app.css`; use SCSS only for custom styles (imported from `app.css` or JS).

## Custom SCSS

### `custom.scss`

Global rules that are not worth expressing as Tailwind utilities:

```scss
[x-cloak] {
    display: none !important;
}
```

Add new global custom styles here, or split into partials and `@import` them from `custom.scss`:

```scss
@import 'components/footer';
@import 'components/cards';
```

### `post-gallery.scss`

Styles for the lightGallery grid on post pages. Imported from `resources/js/gallery.js` so it ships only where needed. Uses SCSS nesting:

```scss
.post-gallery {
    a {
        &:hover img { … }
    }
}
```

TinyMCE editor preview uses inline `content_style` in `editor.js` for gallery blocks inside the editor iframe.

## Build commands

| Command | When |
|---------|------|
| `npm run dev` | Local development — hot reload via Vite |
| `npm run build` | Production — writes `public/build/manifest.json` and bumps `static-asset-version` |

Docker and production deploys require `public/build/` to exist. The entrypoint checks for `manifest.json` on startup.

Cache busting is documented in [Static assets](19-static-assets.md). In short: URLs get `?v=` from `static-asset-version`; `npm run build` bumps it automatically.

After changing styles:

```bash
npm run build
php artisan view:clear   # if cached Blade still references old asset names
```

## Styling conventions

1. **Prefer Tailwind in Blade** — `class="flex items-center gap-4 text-gray-700"` for layout and typography.
2. **Use SCSS for** — nested component rules, pseudo-elements, third-party overrides, or shared non-utility patterns.
3. **Avoid inline styles** in Blade except where required (e.g. dynamic widths).
4. **Admin and guest layouts** use the same `app.css` bundle as the main app layout.

## Key files

| File | Purpose |
|------|---------|
| `vite.config.js` | Vite inputs, Tailwind plugin |
| `package.json` | `tailwindcss`, `sass`, `@tailwindcss/vite`, `@tailwindcss/forms` |
| `resources/views/layouts/app.blade.php` | Main layout, `@vite` |
| `resources/views/layouts/guest.blade.php` | Auth pages |
| `resources/views/layouts/admin.blade.php` | Admin panel |

## Related docs

- [Getting started](01-getting-started.md) — `npm ci && npm run build`
- [Architecture](14-architecture.md) — frontend asset overview
- [Posts & authoring](03-posts-and-authoring.md) — TinyMCE editor
