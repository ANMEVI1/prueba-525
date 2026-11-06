#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
umask 077

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_CONFIG="${SCRIPT_DIR}/../../config/backup.config.json"
CONFIG_PATH="${1:-$DEFAULT_CONFIG}"
PLATFORM="linux"
RUN_ID="$(date -u +%Y%m%dT%H%M%SZ)-$$"
LOG_FILE=""
STAGING_DIR=""
ARCHIVE_TMP=""
ENCRYPTED_TMP=""
CHECKSUM_TMP=""
LOCK_FD=""

log() {
    local level="$1"
    local message="$2"
    local entry
    if ! command -v jq >/dev/null 2>&1; then
        printf '%s %s run_id=%s %s\n' "$(date -u +%FT%TZ)" "$level" "$RUN_ID" "$message" >&2
        return 0
    fi
    entry="$(jq -cn --arg timestamp "$(date -u +%FT%TZ)" --arg level "$level" \
        --arg run_id "$RUN_ID" --arg message "$message" \
        '{timestamp:$timestamp,level:$level,run_id:$run_id,message:$message}')"
    printf '%s\n' "$entry" >&2
    if [[ -n "$LOG_FILE" ]]; then
        printf '%s\n' "$entry" >>"$LOG_FILE"
    fi
}

cleanup() {
    local exit_code=$?
    local cleanup_failed=0
    trap - EXIT
    if [[ -n "$STAGING_DIR" ]]; then
        rm -rf -- "$STAGING_DIR" || cleanup_failed=1
    fi
    if [[ -n "$ARCHIVE_TMP" ]]; then
        rm -f -- "$ARCHIVE_TMP" || cleanup_failed=1
    fi
    if [[ -n "$ENCRYPTED_TMP" ]]; then
        rm -f -- "$ENCRYPTED_TMP" || cleanup_failed=1
    fi
    if [[ -n "$CHECKSUM_TMP" ]]; then
        rm -f -- "$CHECKSUM_TMP" || cleanup_failed=1
    fi
    if (( exit_code != 0 )); then
        log "ERROR" "Backup failed with exit code ${exit_code}"
    fi
    if (( cleanup_failed != 0 )); then
        printf 'ERROR run_id=%s Temporary file cleanup failed.\n' "$RUN_ID" >&2
        (( exit_code != 0 )) || exit_code=1
    fi
    exit "$exit_code"
}
trap cleanup EXIT

fail() {
    log "ERROR" "$1"
    exit 1
}

[[ -f "$CONFIG_PATH" ]] || { printf 'Configuration file not found: %s\n' "$CONFIG_PATH" >&2; exit 2; }
command -v jq >/dev/null 2>&1 || { printf 'Required utility not found: jq\n' >&2; exit 2; }

if [[ "${2:-}" == "--validate-only" ]]; then
    jq -e '
      .schema_version == 1 and
      (.enabled | type == "boolean") and
      (.platform_profiles.linux.source_paths | type == "array" and length > 0) and
      (.platform_profiles.linux.secondary_destination | type == "string") and
      (.encryption.enabled == true) and
      (.encryption.recipients | type == "array" and length > 0) and
      (.retention.daily_copies > 0)
    ' "$CONFIG_PATH" >/dev/null || { printf 'Invalid backup configuration schema.\n' >&2; exit 2; }
    printf 'Configuration schema is valid. This does not validate host paths or enable backups.\n'
    exit 0
fi

[[ "$(jq -r '.enabled' "$CONFIG_PATH")" == "true" ]] || fail "Backups are disabled in configuration"
[[ "$(jq -r '.encryption.enabled' "$CONFIG_PATH")" == "true" ]] || fail "Encryption must remain enabled"

DESTINATION="$(jq -r ".platform_profiles.${PLATFORM}.secondary_destination" "$CONFIG_PATH")"
LOG_DIRECTORY="$(jq -r '.logging.production_log_directory' "$CONFIG_PATH")"
mapfile -t SOURCE_PATHS < <(jq -r ".platform_profiles.${PLATFORM}.source_paths[]" "$CONFIG_PATH")
mapfile -t RECIPIENTS < <(jq -r '.encryption.recipients[]' "$CONFIG_PATH")

[[ "$DESTINATION" != *"REEMPLAZAR"* && "$LOG_DIRECTORY" != *"REEMPLAZAR"* ]] || fail "Replace all example paths before enabling backups"
[[ -d "$DESTINATION" && -w "$DESTINATION" ]] || fail "Secondary destination must already be mounted, exist, and be writable"
mkdir -p -- "$LOG_DIRECTORY"
LOG_FILE="${LOG_DIRECTORY%/}/backup-${PLATFORM}.jsonl"
touch "$LOG_FILE" || fail "Cannot write production log"
chmod 600 "$LOG_FILE"

for utility in tar gzip age sha256sum flock; do
    command -v "$utility" >/dev/null 2>&1 || fail "Required utility not found: ${utility}"
done
(( ${#SOURCE_PATHS[@]} > 0 && ${#RECIPIENTS[@]} > 0 )) || fail "At least one source and encryption recipient are required"

for source_path in "${SOURCE_PATHS[@]}"; do
    [[ "$source_path" == /* && -e "$source_path" && ! -L "$source_path" ]] ||
        fail "Source path must be an existing absolute path and not a symbolic link"
    source_real="$(realpath -e -- "$source_path")"
    destination_real="$(realpath -e -- "$DESTINATION")"
    [[ "$destination_real" != "$source_real" && "$destination_real" != "$source_real/"* ]] ||
        fail "Backup destination must not be inside a source path"
    if find "$source_path" -type l -print -quit | grep -q .; then
        fail "Symbolic links inside configured sources are not supported"
    fi
done

exec {LOCK_FD}>"${DESTINATION%/}/.backup-${PLATFORM}.lock"
flock -n "$LOCK_FD" || fail "Another backup process holds the destination lock"

STAGING_DIR="$(mktemp -d)"
ARCHIVE_TMP="$(mktemp)"
ENCRYPTED_TMP="${DESTINATION%/}/.${RUN_ID}.age.partial"
CHECKSUM_TMP="${DESTINATION%/}/.${RUN_ID}.sha256.partial"

manifest_sources='[]'
index=0
for source_path in "${SOURCE_PATHS[@]}"; do
    index=$((index + 1))
    source_slot="$(printf 'source-%03d' "$index")"
    mkdir -p -- "${STAGING_DIR}/${source_slot}"
    if [[ -d "$source_path" ]]; then
        cp -R --preserve=mode,timestamps -- "${source_path%/}/." "${STAGING_DIR}/${source_slot}/"
    else
        cp --preserve=mode,timestamps -- "$source_path" "${STAGING_DIR}/${source_slot}/"
    fi
    manifest_sources="$(jq -cn --argjson previous "$manifest_sources" \
        --arg slot "$source_slot" --arg path "$source_path" \
        '$previous + [{slot:$slot,original_path:$path}]')"
done

jq -cn --arg run_id "$RUN_ID" --arg created_at "$(date -u +%FT%TZ)" \
    --arg platform "$PLATFORM" --argjson sources "$manifest_sources" \
    '{run_id:$run_id,created_at:$created_at,platform:$platform,sources:$sources}' \
    >"${STAGING_DIR}/backup-manifest.json"

tar -czf "$ARCHIVE_TMP" -C "$STAGING_DIR" .
age_args=()
for recipient in "${RECIPIENTS[@]}"; do
    [[ "$recipient" != *"REEMPLAZAR"* ]] || fail "Replace the example age recipient before enabling backups"
    age_args+=(-r "$recipient")
done
age "${age_args[@]}" -o "$ENCRYPTED_TMP" "$ARCHIVE_TMP"

artifact_name="backup-${PLATFORM}-${RUN_ID}.tar.gz.age"
checksum_name="${artifact_name}.sha256"
artifact_path="${DESTINATION%/}/${artifact_name}"
checksum_path="${DESTINATION%/}/${checksum_name}"
mv -- "$ENCRYPTED_TMP" "$artifact_path"
ENCRYPTED_TMP=""
sha256sum "$artifact_path" >"$CHECKSUM_TMP"
mv -- "$CHECKSUM_TMP" "$checksum_path"
CHECKSUM_TMP=""

if [[ "$(jq -r '.integrity.verify_after_transfer' "$CONFIG_PATH")" == "true" ]]; then
    (cd -- "$DESTINATION" && sha256sum --check --status "$checksum_name") ||
        fail "Post-transfer checksum verification failed"
fi

log "INFO" "Backup completed and encrypted artifact verified: ${artifact_name}"
printf 'Backup completed: %s\n' "$artifact_path"
