#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output="${1:-/tmp/wpcomments.zip}"
stage="$(mktemp -d)"
epoch="${SOURCE_DATE_EPOCH:-$(git -C "$root" show -s --format=%ct HEAD)}"
cleanup() { rm -rf "$stage"; }
trap cleanup EXIT

mkdir -p "$stage/wpcomments"
rsync -a --exclude-from="$root/.distignore" --exclude='/.git' "$root/" "$stage/wpcomments/"

if find "$stage/wpcomments" -type f \( -name '.DS_Store' -o -name '*.log' -o -name '*.sql' \) -print -quit | grep -q .; then
    echo 'FAIL: package contains a prohibited transient file' >&2
    exit 1
fi

find "$stage/wpcomments" -exec touch -d "@$epoch" {} +
rm -f "$output"
(
    cd "$stage"
    find wpcomments -type f -print0 | LC_ALL=C sort -z | xargs -0 zip -X -q "$output"
)
unzip -tq "$output"
printf 'PASS: package built: %s\n' "$output"
