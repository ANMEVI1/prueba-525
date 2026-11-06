#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

TAG="# IESP-BACKUP-SCHEDULE"
command -v crontab >/dev/null 2>&1 || { printf 'Required utility not found: crontab\n' >&2; exit 2; }
crontab_content="$(crontab -l 2>/dev/null || true)"
grep -Fq "$TAG" <<<"$crontab_content" || {
    printf 'No tagged IESP backup schedule exists in this user crontab.\n' >&2
    exit 1
}

temporary_crontab="$(mktemp)"
trap 'rm -f -- "$temporary_crontab"' EXIT
printf '%s\n' "$crontab_content" | awk -v tag="$TAG" '
    skip > 0 { skip--; next }
    $0 == tag { skip = 2; found = 1; next }
    { print }
    END { if (!found) exit 1 }
' >"$temporary_crontab"
crontab "$temporary_crontab"
printf 'Removed the tagged IESP backup schedule from this user crontab.\n'
