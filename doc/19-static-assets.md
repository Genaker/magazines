# Static assets — architecture & generation

How CSS and JavaScript are built, served, and cache-busted in production.

## Architecture

```
resources/js/          resources/css/
       │                      │
       └──────────┬───────────┘
                  ▼
            Vite (npm run build)
                  │
                  ▼
         public/build/          public/js/*.js (legacy fallback)
    manifest.json + hashed      when manifest missing
    CSS/JS bundles
                  │
                  ▼
    Blade layouts (@vite / static_asset())
                  │
                  ▼
    URLs with ?v={version}  ← static-asset-version file
```

| Piece | Role |
|-------|------|
| **Vite** | Bundles `resources/js/app.js`, `editor.js`, `gallery.js`, and `resources/css/app.css` (+ SCSS) into hashed files under `public/build/`. |
| **`public/js/post.js`, `public/js/app.js`** | Loaded when `public/build/manifest.json` is missing (CDN Tailwind fallback in layouts). |
| **`static-asset-version`** | Integer at the project root. Appended as `?v=` on every static CSS/JS URL so browsers fetch fresh files after deploy. |
| **`StaticAssetVersion`** (`app/Support/StaticAssetVersion.php`) | Reads, bumps, and appends the version query string. |
| **`static_asset()`** (`app/helpers.php`) | Blade helper: `asset('js/post.js')` + `?v=`. |
| **`Vite::createAssetPathsUsing()`** (`AppServiceProvider`) | Same `?v=` for Vite-generated `@vite` URLs. |

Layouts use `@vite` when the manifest exists; otherwise they fall back to CDN Tailwind + `static_asset('js/app.js')`. Post pages load `static_asset('js/post.js')` for likes, comments, and share UI.

## Generation (build workflow)

| Command | What it does |
|---------|----------------|
| `npm run dev` | Vite dev server with hot reload. Does **not** bump the version. |
| `npm run build` | `vite build` then `php artisan static-version:bump` |
| `php artisan static-version:bump` | Increment `static-asset-version` manually |
| `php artisan static-version:bump --show` | Print current version without changing it |

Typical deploy or local production check:

```bash
npm ci
npm run build
```

Docker and production require `public/build/manifest.json`. The entrypoint checks for it on startup.

After CSS/JS changes, run `npm run build` so hashed filenames and the cache-bust version both update. If Blade views are cached, run `php artisan view:clear`.

## Key files

| File | Purpose |
|------|---------|
| `static-asset-version` | Current cache-bust integer |
| `app/Support/StaticAssetVersion.php` | Version read/bump/append logic |
| `app/Console/Commands/BumpStaticAssetVersionCommand.php` | `static-version:bump` command |
| `app/helpers.php` | `static_asset()` helper |
| `app/Providers/AppServiceProvider.php` | Vite URL versioning |
| `vite.config.js` | Vite entry points |
| `package.json` | `build` script chains Vite + bump |
| `tests/Feature/StaticAssetVersionTest.php` | Version helper and command tests |

## Related docs

- [Styling](18-styling.md) — Tailwind, SCSS, and editor styles
- [Architecture](14-architecture.md) — overall app structure
- [Getting started](01-getting-started.md) — first-time `npm run build`
