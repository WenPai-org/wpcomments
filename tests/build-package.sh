#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output="${1:-/tmp/wpcomments.zip}"
stage="$(mktemp -d)"

mkdir -p "$stage/wpcomments"
rsync -a --exclude-from="$root/.distignore" --exclude='/.git' "$root/" "$stage/wpcomments/"

if find "$stage/wpcomments" -type f \( -name '.DS_Store' -o -name '*.log' -o -name '*.sql' \) -print -quit | grep -q .; then
    echo 'FAIL: package contains a prohibited transient file' >&2
    exit 1
fi

(cd "$stage" && zip -qr "$output" wpcomments)
unzip -tq "$output"
printf 'PASS: package built: %s\n' "$output"
