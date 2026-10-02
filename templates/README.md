# Site templates (view overrides)

Set the active theme in `.env`:

```env
SITE_TEMPLATE=magazine
```

Blade resolves views from `templates/{name}/` **first**, using the **same paths** as `resources/views/`. Any view not present in the theme falls back to the default.

## Example layout

```
templates/
  magazine/
    layouts/
      app.blade.php          ← overrides resources/views/layouts/app.blade.php
    authors/
      show.blade.php         ← overrides resources/views/authors/show.blade.php
    partials/
      footer.blade.php       ← only this partial is customized
```

Copy a file from `resources/views/` into the matching path under your theme folder, edit it, and clear compiled views if needed:

```bash
php artisan view:clear
```

## Rules

- Theme folder names: lowercase letters, numbers, `-`, `_` (must start with a letter or number).
- Leave `SITE_TEMPLATE` empty or set to `default` to use built-in views only.
- `@include`, layouts, components, and `view('…')` calls all respect the active theme.
