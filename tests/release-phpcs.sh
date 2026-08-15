#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"
manifest="tests/release-phpcs-files.txt"
base="${RELEASE_PHPCS_BASE:-origin/main}"

test -x vendor/bin/phpcs || { echo 'FAIL: run composer install first' >&2; exit 1; }
test -f "$manifest" || { echo 'FAIL: release PHPCS manifest is missing' >&2; exit 1; }

mapfile -t files < <(grep -Ev '^[[:space:]]*(#|$)' "$manifest" | LC_ALL=C sort -u)
((${#files[@]} > 0)) || { echo 'SKIP: release PHPCS manifest has no PHP files'; exit 2; }

for file in "${files[@]}"; do
    [[ "$file" == *.php && -f "$file" ]] || { printf 'FAIL: invalid release PHPCS file: %s
' "$file" >&2; exit 1; }
done

if git rev-parse --verify "$base" >/dev/null 2>&1; then
    mapfile -t changed < <({
        git diff --name-only --diff-filter=ACMR "$base"...HEAD
        git diff --name-only --diff-filter=ACMR
        git diff --cached --name-only --diff-filter=ACMR
    } | grep -E '\.php$' | grep -Ev '^(tests|vendor|build|docs|\.github)/' | LC_ALL=C sort -u)
    for file in "${changed[@]}"; do
        grep -Fxq "$file" "$manifest" || { printf 'FAIL: changed release PHP is missing from %s: %s
' "$manifest" "$file" >&2; exit 1; }
    done
fi

if grep -nHE 'phpcs:(disable|set)' "${files[@]}"; then
    echo 'FAIL: blanket PHPCS controls are forbidden in release files' >&2
    exit 1
fi

vendor/bin/phpcs --standard=phpcs.release.xml.dist "${files[@]}"
printf 'PASS: release PHPCS gate; files=%d
' "${#files[@]}"
