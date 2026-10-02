# Progressive Web App (PWA)

## Overview

Magazines can be **installed on a phone or desktop** like a native app. The site ships with a web app manifest, service worker, and iOS meta tags.

| | |
|---|---|
| **Manifest** | `GET /site.webmanifest` |
| **Service worker** | `public/sw.js` |
| **Icons** | `public/images/pwa/` (192, 512, apple-touch-icon) |

## Install on iPhone (Safari)

1. Open the site in **Safari** (HTTPS recommended in production).
2. Tap **Share** → **Add to Home Screen**.
3. The app opens in standalone mode without the browser chrome.

## Install on Android / Chrome

1. Open the site in Chrome.
2. Use **Install app** or **Add to Home screen** from the browser menu (when criteria are met: manifest + service worker + HTTPS).

## Manifest contents

Generated dynamically by `WebManifestController` using:

- Site name from `site_settings` (or `APP_NAME`)
- `start_url`: `/`
- `display`: `standalone`
- Theme color `#111827`, background `#f3f4f6`
- Icons at 192×192 and 512×512

## Service worker

`public/sw.js` registers on page load (HTTPS or localhost). It enables install prompts and basic offline fetch passthrough. Cache version: `magazines-pwa-v1`.

Registration runs from `resources/js/app.js` (Vite bundle).

## iOS meta tags

Included via `resources/views/partials/pwa.blade.php`:

- `apple-mobile-web-app-capable`
- `apple-mobile-web-app-title`
- `apple-touch-icon` (180×180)

## Production checklist

- Set `APP_URL` to your **HTTPS** domain.
- Rebuild frontend after changes: `npm run build`.
- Confirm `/site.webmanifest` and `/sw.js` are reachable.

## Key files

- `app/Http/Controllers/WebManifestController.php`
- `resources/views/partials/pwa.blade.php`
- `public/sw.js`
- `tests/Feature/WebManifestTest.php`

## Related docs

- [Getting started](01-getting-started.md)
