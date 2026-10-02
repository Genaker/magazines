#!/usr/bin/env sh
# Auth + registration Playwright e2e (Docker test stack on port 13307).
set -e
cd "$(dirname "$0")/.."
exec sh docker/e2e.sh tests/e2e/auth/register-flow.spec.ts tests/e2e/auth/passwordless-registration.spec.ts tests/e2e/auth/magic-login-security.spec.ts "$@"
