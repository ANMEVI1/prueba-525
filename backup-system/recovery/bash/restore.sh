#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
umask 077

usage() {
    printf 'Usage: %s <artifact.tar.gz.age> <age-identity-file> <new-empty-target-directory>\n' "$0" >&2
    exit 2
}

[[ $# -eq 3 ]] || usage
ARTIFACT="$(realpath -e -- "$1")"
IDENTITY="$(realpath -e -- "$2")"
TARGET="$3"
CHECKSUM="${ARTIFACT}.sha256"
TEMP_DIR=""

cleanup() {
    [[ -z "$TEMP_DIR" ]] || rm -rf -- "$TEMP_DIR"
}
trap cleanup EXIT

for utility in age tar sha256sum; do
    command -v "$utility" >/dev/null 2>&1 || { printf 'Required utility not found: %s\n' "$utility" >&2; exit 2; }
done
[[ -f "$ARTIFACT" && -f "$IDENTITY" && -f "$CHECKSUM" ]] || {
    printf 'Artifact, identity, and checksum sidecar must all exist.\n' >&2
    exit 2
}
[[ ! -e "$TARGET" ]] || { printf 'Restore target must not already exist.\n' >&2; exit 2; }

(cd -- "$(dirname -- "$ARTIFACT")" && sha256sum --check "$(basename -- "$CHECKSUM")") ||
    { printf 'Artifact checksum verification failed.\n' >&2; exit 1; }

parent_dir="$(dirname -- "$TARGET")"
mkdir -p -- "$parent_dir"
TEMP_DIR="$(mktemp -d "${parent_dir%/}/.restore.XXXXXXXX")"
archive_tmp="${TEMP_DIR}/decrypted.tar.gz"
extract_dir="${TEMP_DIR}/contents"
mkdir -m 700 -- "$extract_dir"

age --decrypt --identity "$IDENTITY" --output "$archive_tmp" "$ARTIFACT"
archive_listing="$(tar -tzf "$archive_tmp")"
while IFS= read -r entry; do
    [[ -n "$entry" ]] || continue
    case "$entry" in
        /*|../*|*/../*|*/..|..|[A-Za-z]:*|\\\\*)
            printf 'Unsafe archive path rejected: %s\n' "$entry" >&2
            exit 1
            ;;
    esac
done <<<"$archive_listing"
verbose_listing="$(tar -tvzf "$archive_tmp")"
while IFS= read -r entry; do
    case "${entry:0:1}" in
        -|d) ;;
        *)
            printf 'Unsupported archive entry type rejected.\n' >&2
            exit 1
            ;;
    esac
done <<<"$verbose_listing"
tar -xzf "$archive_tmp" -C "$extract_dir"
[[ -f "${extract_dir}/backup-manifest.json" ]] || {
    printf 'Archive manifest is missing; refusing to publish the restore.\n' >&2
    exit 1
}

# Publish only after decryption and archive validation have completed.
mv -- "$extract_dir" "$TARGET"
printf 'Restore completed in: %s\n' "$TARGET"
