# Authentication

## Overview

Users can register with email/password, sign in via **magic link** (default) or **password**, and must verify email before writing or using social features.

Banned users are logged out automatically on every request.

## Registration

| | |
|---|---|
| **Who** | Guests |
| **Routes** | `GET/POST /register` |
| **After signup (magic link login on)** | Redirect to `/login` — enter 6-digit code from email (verifies email **and** signs in) |
| **After signup (magic link login off)** | Redirect to `/verify-email` — click link, then sign in |

**Key files:** `RegisteredUserController`, `App\Support\MagicLoginIssuer`, `resources/views/auth/register.blade.php`, `App\Support\RegistrationGate`

### Manual test (local Docker)

With **Magic link login** enabled (Admin → Settings, default on):

1. Open **http://127.0.0.1:8888/register**
2. Fill name, username, email — with or without password (or check “magic link only” for passwordless)
3. Submit → redirect to **http://127.0.0.1:8888/login** with the 6-digit code field
4. Copy the code from the green debug box (when `APP_DEBUG=true`) or from **http://127.0.0.1:8025** (Mailpit)
5. Enter code → **Sign in** → you should be logged in with email verified (nav shows **Write**)

Automated coverage: `sh scripts/test-auth.sh` (PHPUnit), `sh scripts/test-auth-e2e.sh` (Playwright). See [Testing](15-testing.md).

### Open vs invite-only

Super admins control sign-up in **Admin → Settings**:

| Toggle | Effect |
|--------|--------|
| **Open registration** | Anyone can sign up at `/register` (default). |
| **Registration invites** | When open registration is off, guests can still register with a valid invite code or `/register?invite=CODE` link. |

If both are off, `/register` returns 404. Nav and login pages only promote open registration; invite-only communities share links from admin settings.

**Key files:** `RegistrationInvite` model, `Admin\RegistrationInviteController`, `EnsureRegistrationAvailable` middleware

## Login

### Magic link (default)

| | |
|---|---|
| **Routes** | `GET /login`, `POST /login/magic-link`, `GET /login/magic-link/verify`, `POST /login/magic-link/code` |
| **Flow** | Enter email → receive link + 6-digit code → enter code or click link |
| **Expiry** | 15 minutes |

In local debug mode, the code is also shown on the login page. Emails go to Mailpit.

**Key files:** `MagicLinkController`, `LoginLink` model, `resources/views/auth/login.blade.php`, `resources/views/emails/magic-login.blade.php`

#### How it works

1. Guest enters email on `/login` and submits **Email me a link**.
2. If an account exists (and is not banned), the app creates a `login_links` row with:
   - a random **token** (for the clickable URL),
   - a random **6-digit code** (e.g. `707999`),
   - **15-minute** expiry.
   Token and code are stored **hashed**; plain values exist only in the email (and in debug UI — see below).
3. An email is sent with both the **magic link** and the **code**.
4. The login page switches to the code-entry step (`magic_login_email` stored in session).
5. Sign-in succeeds by either:
   - clicking the link (`GET /login/magic-link/verify?token=…&email=…`), or
   - submitting the code (`POST /login/magic-link/code`).
6. The link row is marked **used**; the same link/code cannot be reused.

If no account matches the email, the UI still shows a generic success message (no account enumeration).

Resend uses the same flow; a new token/code pair is issued.

### Passwordless registration

When **Magic link login** is enabled, `/register` offers:

> I don't want a password — email me a sign-in code instead

Checking it creates an account with `password = NULL`. After register, the same **6-digit PIN flow** as login applies: enter the code on `/login` to verify your email and sign in. Future sign-ins use magic link (or **Forgot password** / **Profile → Set password** to add a password later).

When magic link login is **enabled**, password registration also uses the PIN step after signup (no separate `/verify-email` step). Password login works only after the email is verified (via PIN or legacy link flow).

**Key files:** `RegisteredUserController`, migration `V00043_make_users_password_nullable`

### Local debug helpers (`APP_DEBUG=true`)

These shortcuts appear **only when `APP_DEBUG=true`** (Docker local stack default). They are **not shown in production**.

| Where | What you see | Purpose |
|-------|----------------|---------|
| `/login` (after register or requesting a link) | Green box: **Your sign-in code (also emailed)** + 6-digit code | Same code as in the email — skip Mailpit during development |
| `/login` (after register) | Status: “Enter the 6-digit code we emailed…” | Registration uses the same PIN step as login (`registration-code-sent`) |
| `/admin/login` (after requesting a link) | Same green code box | Admin magic-link sign-in |
| `/login` code field | Code pre-filled from session | Click **Sign in** immediately if you just requested a link |
| `/login` green box | **Open Mailpit inbox →** | Opens `MAILPIT_WEB_URL` (default http://127.0.0.1:8025) to read the full email with link + code |
| `/verify-email` | Green box: **Verify now (local dev — no Mailpit needed)** + link | One-click email verification after register or resend |
| Laravel log | `Magic login issued` with `code`, `url`, `email` | Same values as Mailpit when debugging CLI/queue issues |

**Session keys (flash, one request):** `dev_login_code`, `dev_mailpit_url`, `dev_verification_url`.

**Production behaviour:** only the email is sent; login shows the code field empty; verification has no on-page link.

**Key files:** `MagicLinkController`, `MagicLoginIssuer`, `RegisteredUserController` (sets `dev_login_code`), `EmailVerificationNotificationController` (set `dev_verification_url`), `resources/views/auth/login.blade.php`, `resources/views/auth/verify-email.blade.php`

### Password login

| | |
|---|---|
| **Routes** | `GET /login/password`, `POST /login` |
| **Link** | “Use password instead” on the magic link login page |

**Key files:** `AuthenticatedSessionController`, `resources/views/auth/login-password.blade.php`

## Admin login

Staff sign in at **`/admin/login`**, not `/login`. Admin uses a **separate session cookie** (path `/admin`) — see [Admin panel → Sign in](13-admin.md#sign-in).

When **Magic link login** is enabled:

| | |
|---|---|
| **Routes** | `GET /admin/login`, `POST /admin/login/magic-link`, `GET /admin/login/magic-link/verify`, `POST /admin/login/magic-link/code` |
| **Password** | `GET /admin/login/password`, `POST /admin/login` |
| **Who** | `admin` or `super_admin` only |

Flow and debug helpers match [site magic link](#magic-link-default). Non-admin emails do not receive a link.

**Key files:** `AdminAuthenticatedSessionController`, `MagicLinkController`, `App\Support\AdminSession`

## Password reset

| Route | Purpose |
|-------|---------|
| `GET/POST /forgot-password` | Request reset email |
| `GET/POST /reset-password/{token}` | Set new password |

## Email verification

Required for routes behind `verified` middleware (`/write`, `/me/posts`, comments, follows, etc.).

| Route | Purpose |
|-------|---------|
| `GET /verify-email` | Prompt to verify |
| `GET /verify-email/{id}/{hash}` | Verify from email link |
| `POST /email/verification-notification` | Resend verification email |

Changing email on the profile page resets verification status.

## Account security

| Feature | Route | Who |
|---------|-------|-----|
| Update password | `PUT /password` | Logged in (passwordless users see **Set password** — no current password required) |
| Confirm password | `GET/POST /confirm-password` | Logged in (sensitive actions) |
| Logout | `POST /logout` | Logged in |
| Unlock magic-link lock | `php artisan app:user:unlock-magic-login {email}` | CLI / admin |

### Email verification before sign-in

When **Magic link login** is enabled (default):

1. Register → 6-digit code emailed → `/login` code step (guest)
2. Enter correct code → email marked verified **and** you are signed in (one step)

When magic link login is **disabled**:

1. Register → verification email → `/verify-email` (guest)
2. Click signed link → email marked verified → redirect to `/login`
3. Sign in with password (magic link unavailable)

Password login always requires a verified email. Magic-link PIN login verifies unverified accounts on successful code entry.

### Magic-link brute-force protection

Failed **6-digit code** or **invalid link token** attempts are counted on the user record (`magic_login_failed_attempts`). After **`MAGIC_LOGIN_MAX_ATTEMPTS`** failures (default **5**, see `config/magic-login.php`), the account is **locked** (`magic_login_locked_at`) until an administrator runs:

```bash
php artisan app:user:unlock-magic-login user@example.com
# or: ./scripts/docker-artisan.sh user:unlock-magic-login user@example.com
```

HTTP throttling (6 requests/minute) still applies on send-code and verify-code routes. Successful sign-in clears the failure counter.

Unverified accounts **can** request magic-link codes when magic link login is enabled; a correct code verifies the email and signs them in. Password login remains blocked until verified.

## Middleware

| Middleware | Effect |
|------------|--------|
| `auth` | Must be logged in |
| `verified` | Email must be verified |
| `guest` | Must not be logged in |
| `EnsureUserIsNotBanned` | Banned users are logged out |

**Key files:** `routes/auth.php`, `app/Models/User.php`, `bootstrap/app.php`

## Related docs

- [User profile](12-user-profile.md)
- [Admin panel](13-admin.md) — ban users
