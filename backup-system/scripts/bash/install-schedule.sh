#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
umask 077

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/../.." && pwd)"
CONFIG_PATH="${1:-${PROJECT_DIR}/config/backup.config.json}"
TAG="# IESP-BACKUP-SCHEDULE"

shell_quote() {
    local value="$1"
    value="${value//\'/\'\"\'\"\'}"
    printf "'%s'" "$value"
}

[[ -f "$CONFIG_PATH" ]] || { printf 'Configuration file not found: %s\n' "$CONFIG_PATH" >&2; exit 2; }
for utility in jq crontab realpath; do
    command -v "$utility" >/dev/null 2>&1 || { printf 'Required utility not found: %s\n' "$utility" >&2; exit 2; }
done

jq -e '.enabled == true and .schedule.frequency == "daily" and
    (.schedule.local_time | test("^(?:[01][0-9]|2[0-3]):[0-5][0-9]$")) and
    (.timezone | type == "string" and test("^[A-Za-z0-9_+/-]+$")) and
    .encryption.enabled == true' "$CONFIG_PATH" >/dev/null ||
    { printf 'Configuration must enable encrypted daily backups and use HH:MM local time.\n' >&2; exit 2; }

config_abs="$(realpath -e -- "$CONFIG_PATH")"
backup_abs="$(realpath -e -- "${SCRIPT_DIR}/backup.sh")"
prune_abs="$(realpath -e -- "${SCRIPT_DIR}/prune.sh")"
timezone="$(jq -r '.timezone' "$CONFIG_PATH")"
local_time="$(jq -r '.schedule.local_time' "$CONFIG_PATH")"
minute="${local_time#*:}"
hour="${local_time%%:*}"
log_directory="$(jq -r '.logging.production_log_directory' "$CONFIG_PATH")"
[[ "$log_directory" != *"REEMPLAZAR"* && -d "$log_directory" && -w "$log_directory" ]] || {
    printf 'Configured production log directory must be replaced, exist, and be writable.\n' >&2
    exit 2
}
quoted_config="$(shell_quote "$config_abs")"
quoted_backup="$(shell_quote "$backup_abs")"
quoted_prune="$(shell_quote "$prune_abs")"
quoted_log="$(shell_quote "${log_directory%/}/scheduler.jsonl")"
crontab_content="$(crontab -l 2>/dev/null || true)"
if grep -Fq "$TAG" <<<"$crontab_content"; then
    printf 'A backup schedule already exists for this user. Review/remove its tagged entry before reinstalling.\n' >&2
    exit 1
fi

cron_command="{ /bin/bash ${quoted_backup} ${quoted_config} && if [ \"\$(jq -r '.retention.enabled' ${quoted_config})\" = true ]; then /bin/bash ${quoted_prune} ${quoted_config} --apply; fi; } >>${quoted_log} 2>&1"
cron_entry="${minute} ${hour} * * * ${cron_command}"
printf '%s\n' "$crontab_content" "$TAG" "CRON_TZ=${timezone}" "$cron_entry" | sed '/^$/d' | crontab -
printf 'Installed daily backup schedule for this user at %s (%s).\n' "$local_time" "$timezone"
printf 'The schedule runs retention only after a successful backup and when retention.enabled is true.\n'
