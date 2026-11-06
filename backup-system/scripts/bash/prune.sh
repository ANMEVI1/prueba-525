#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
umask 077

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_PATH="${1:-${SCRIPT_DIR}/../../config/backup.config.json}"
MODE="${2:---dry-run}"
PLATFORM="linux"

if [[ "$MODE" != "--dry-run" && "$MODE" != "--apply" ]]; then
    printf 'Usage: %s [config.json] [--dry-run|--apply]\n' "$0" >&2
    exit 2
fi
[[ -f "$CONFIG_PATH" ]] || { printf 'Configuration file not found: %s\n' "$CONFIG_PATH" >&2; exit 2; }
command -v jq >/dev/null 2>&1 || { printf 'Required utility not found: jq\n' >&2; exit 2; }

DESTINATION="$(jq -r ".platform_profiles.${PLATFORM}.secondary_destination" "$CONFIG_PATH")"
DAILY_LIMIT="$(jq -er '.retention.daily_copies | select(type == "number" and . >= 1 and floor == .)' "$CONFIG_PATH")"
WEEKLY_LIMIT="$(jq -er '.retention.weekly_copies | select(type == "number" and . >= 1 and floor == .)' "$CONFIG_PATH")"
MONTHLY_LIMIT="$(jq -er '.retention.monthly_copies | select(type == "number" and . >= 1 and floor == .)' "$CONFIG_PATH")"
RETENTION_ENABLED="$(jq -r '.retention.enabled' "$CONFIG_PATH")"

[[ "$DESTINATION" != *"REEMPLAZAR"* && -d "$DESTINATION" ]] || {
    printf 'Configured Linux destination must be replaced and exist.\n' >&2
    exit 2
}
if [[ "$MODE" == "--apply" && "$RETENTION_ENABLED" != "true" ]]; then
    printf 'Retention is disabled in configuration; refusing to delete artifacts.\n' >&2
    exit 2
fi

declare -a entries=()
shopt -s nullglob
for artifact_path in "${DESTINATION%/}"/backup-linux-*.tar.gz.age; do
    artifact_name="${artifact_path##*/}"
    if [[ ! "$artifact_name" =~ ^backup-linux-([0-9]{8}T[0-9]{6}Z)-.*\.tar\.gz\.age$ ]]; then
        continue
    fi
    checksum_name="${artifact_name}.sha256"
    [[ -f "${DESTINATION%/}/${checksum_name}" ]] || {
        printf 'Missing checksum sidecar; refusing retention: %s\n' "$artifact_name" >&2
        exit 1
    }
    (cd -- "$DESTINATION" && sha256sum --check --status "$checksum_name") || {
        printf 'Invalid checksum; refusing retention: %s\n' "$artifact_name" >&2
        exit 1
    }
    entries+=("${BASH_REMATCH[1]}|${artifact_name}")
done

if (( ${#entries[@]} == 0 )); then
    printf 'No verified Linux backup artifacts found; nothing to prune.\n'
    exit 0
fi

mapfile -t entries < <(printf '%s\n' "${entries[@]}" | sort -r)
declare -A keep=()
declare -A daily_buckets=()
declare -A weekly_buckets=()
declare -A monthly_buckets=()
daily_count=0
weekly_count=0
monthly_count=0

for entry in "${entries[@]}"; do
    stamp="${entry%%|*}"
    artifact_name="${entry#*|}"
    date_key="${stamp:0:8}"
    parsed_date="${stamp:0:4}-${stamp:4:2}-${stamp:6:2} ${stamp:9:2}:${stamp:11:2}:${stamp:13:2}"
    week_key="$(date -u -d "$parsed_date" +%G-%V)" || {
        printf 'Invalid UTC timestamp in artifact name: %s\n' "$artifact_name" >&2
        exit 1
    }
    month_key="${stamp:0:6}"

    if [[ -z "${daily_buckets[$date_key]+present}" ]]; then
        daily_buckets[$date_key]=1
        if (( daily_count < DAILY_LIMIT )); then
            keep[$artifact_name]=1
            daily_count=$((daily_count + 1))
        fi
    fi
    if [[ -z "${weekly_buckets[$week_key]+present}" ]]; then
        weekly_buckets[$week_key]=1
        if (( weekly_count < WEEKLY_LIMIT )); then
            keep[$artifact_name]=1
            weekly_count=$((weekly_count + 1))
        fi
    fi
    if [[ -z "${monthly_buckets[$month_key]+present}" ]]; then
        monthly_buckets[$month_key]=1
        if (( monthly_count < MONTHLY_LIMIT )); then
            keep[$artifact_name]=1
            monthly_count=$((monthly_count + 1))
        fi
    fi
done

delete_count=0
for entry in "${entries[@]}"; do
    artifact_name="${entry#*|}"
    if [[ -n "${keep[$artifact_name]+present}" ]]; then
        continue
    fi
    delete_count=$((delete_count + 1))
    if [[ "$MODE" == "--apply" ]]; then
        rm -- "${DESTINATION%/}/${artifact_name}"
        rm -- "${DESTINATION%/}/${artifact_name}.sha256"
        printf 'Deleted verified artifact: %s\n' "$artifact_name"
    else
        printf 'Would delete verified artifact: %s\n' "$artifact_name"
    fi
done

if (( delete_count == 0 )); then
    printf 'Retention selected no artifacts for deletion.\n'
elif [[ "$MODE" == "--dry-run" ]]; then
    printf 'Dry run only: %s verified artifact(s) would be deleted.\n' "$delete_count"
fi
