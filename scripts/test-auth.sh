#!/usr/bin/env sh
# Auth + registration PHPUnit (Docker test stack on port 13307).
set -e
cd "$(dirname "$0")/.."
exec sh docker/test.sh --filter='Registration|VerifiedLogin|PasswordlessRegistration|AuthFlowIntegration|RegistrationInvite|MagicLoginSecurity' "$@"
