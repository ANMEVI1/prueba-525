#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
umask 077

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG_EXAMPLE="${PROJECT_DIR}/config/backup.config.example.json"
BACKUP_SCRIPT="${PROJECT_DIR}/scripts/bash/backup.sh"
PRUNE_SCRIPT="${PROJECT_DIR}/scripts/bash/prune.sh"
RESTORE_SCRIPT="${PROJECT_DIR}/recovery/bash/restore.sh"
for utility in age age-keygen jq tar gzip sha256sum flock realpath; do
    command -v "$utility" >/dev/null 2>&1 || {
        printf 'Required test utility not found: %s\n' "$utility" >&2
        exit 2
    }
done

TEST_ROOT="$(mktemp -d)"
cleanup() {
    rm -rf -- "$TEST_ROOT"
}
trap cleanup EXIT

SOURCE_DIR="${TEST_ROOT}/source"
DESTINATION_DIR="${TEST_ROOT}/destination"
RESTORE_DIR="${TEST_ROOT}/restored"
IDENTITY_FILE="${TEST_ROOT}/test-identity.txt"
CONFIG_FILE="${TEST_ROOT}/backup.config.json"
mkdir -m 700 -- "$SOURCE_DIR" "$DESTINATION_DIR"
printf 'IESP synthetic recovery test\n' >"${SOURCE_DIR}/synthetic.txt"

age-keygen -o "$IDENTITY_FILE" >/dev/null
RECIPIENT="$(sed -n 's/^# public key: //p' "$IDENTITY_FILE")"
[[ "$RECIPIENT" == age1* ]] || { printf 'Failed to generate a test-only age recipient.\n' >&2; exit 1; }

jq --arg source "$SOURCE_DIR" --arg destination "$DESTINATION_DIR" \
    --arg log_directory "$TEST_ROOT/logs" --arg recipient "$RECIPIENT" \
    '.enabled = true
    | .platform_profiles.linux.source_paths = [$source]
    | .platform_profiles.linux.secondary_destination = $destination
    | .logging.production_log_directory = $log_directory
    | .encryption.recipients = [$recipient]
    | .retention.enabled = false' "$CONFIG_EXAMPLE" >"$CONFIG_FILE"
mkdir -m 700 -- "$TEST_ROOT/logs"

"$BACKUP_SCRIPT" "$CONFIG_FILE"
shopt -s nullglob
artifacts=("${DESTINATION_DIR}"/backup-linux-*.tar.gz.age)
(( ${#artifacts[@]} == 1 )) || { printf 'Expected exactly one test backup artifact.\n' >&2; exit 1; }

"$RESTORE_SCRIPT" "${artifacts[0]}" "$IDENTITY_FILE" "$RESTORE_DIR"
[[ "$(cat "${RESTORE_DIR}/source-001/synthetic.txt")" == "IESP synthetic recovery test" ]] ||
    { printf 'Restored content mismatch.\n' >&2; exit 1; }

if "$RESTORE_SCRIPT" "${artifacts[0]}" "$IDENTITY_FILE" "$RESTORE_DIR" >/dev/null 2>&1; then
    printf 'Restore unexpectedly overwrote an existing target.\n' >&2
    exit 1
fi

if "$PRUNE_SCRIPT" "$CONFIG_FILE" --apply >/dev/null 2>&1; then
    printf 'Retention unexpectedly ran while disabled.\n' >&2
    exit 1
fi

jq '.retention.enabled = true | .retention.daily_copies = 1
    | .retention.weekly_copies = 1 | .retention.monthly_copies = 12' \
    "$CONFIG_FILE" >"${CONFIG_FILE}.next"
mv -- "${CONFIG_FILE}.next" "$CONFIG_FILE"
declare -a synthetic_artifacts=()
for month_offset in $(seq 0 12); do
    stamp="$(date -u -d "$(date -u +%Y-%m-15) -${month_offset} month" +%Y%m%dT120000Z)"
    artifact_name="backup-linux-${stamp}-synthetic${month_offset}.tar.gz.age"
    artifact_path="${DESTINATION_DIR}/${artifact_name}"
    printf 'synthetic retention data %s\n' "$month_offset" >"$artifact_path"
    sha256sum "$artifact_path" >"${artifact_path}.sha256"
    synthetic_artifacts+=("$artifact_path")
done
oldest_artifact="${synthetic_artifacts[12]}"

"$PRUNE_SCRIPT" "$CONFIG_FILE" --dry-run >/dev/null
[[ -f "$oldest_artifact" ]] || { printf 'Retention dry-run deleted an artifact.\n' >&2; exit 1; }
"$PRUNE_SCRIPT" "$CONFIG_FILE" --apply
[[ ! -e "$oldest_artifact" ]] || { printf 'Retention did not remove the expired synthetic artifact.\n' >&2; exit 1; }

printf 'Synthetic backup, encrypted restore, no-overwrite guard, and retention tests passed.\n'
