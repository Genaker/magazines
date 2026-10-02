# Localization (i18n)

## Overview

Magazines uses **Laravel’s built-in translation system** with session-based locale switching. English is the default; **Ukrainian** (`ua`, ISO 3166-1 alpha-2 for Ukraine) is included as a second language.

| | |
|---|---|
| **Default locale** | `en` (`APP_LOCALE` in `.env`) when not configured in admin |
| **Available** | `en`, `ua` — see `config/locales.php` |
| **Enabled / default** | Super admin: **Admin → Settings → Language** |
| **Switch route** | `GET /locale/{locale}` (`locale.switch`) — only when multiple languages are enabled |

## Switching language

Users click **EN** or **UA** in the navigation bar when more than one language is enabled (also on login pages). The choice is stored in the session and applied on every request by `SetLocale` middleware. With a single enabled language, the switcher is hidden and everyone sees the default.

## Translation files

| Path | Purpose |
|------|---------|
| `lang/en/app.php`, `lang/ua/app.php` | Main UI strings (nav, feed, footer, posts) |
| `lang/en.json`, `lang/ua.json` | Short labels (Email, Password, Register, etc.) |
| `lang/en/auth.php`, `lang/ua/auth.php` | Authentication error messages |
| `lang/en/pagination.php`, `lang/ua/pagination.php` | Pagination controls |

Use in Blade:

```blade
{{ __('app.home') }}
{{ __('Email') }}
```

Add a new string to **both** `lang/en/app.php` and `lang/ua/app.php`.

## Configuration

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

If a Ukrainian string is missing, Laravel falls back to English (`APP_FALLBACK_LOCALE`).

## Key files

- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/LocaleController.php`
- `app/Support/SiteLocale.php`
- `resources/views/partials/locale-switcher.blade.php`
- `tests/Feature/LocaleTest.php`

## Adding another language

1. Add the locale code to `config/locales.php` (`supported` and `labels`).
2. Copy `lang/en/app.php` → `lang/{code}/app.php` and translate.
3. Copy `lang/en.json` → `lang/{code}.json` and translate.
4. Optionally add `lang/{code}/auth.php` and `pagination.php`.

## Related docs

- [Getting started](01-getting-started.md) — environment setup
