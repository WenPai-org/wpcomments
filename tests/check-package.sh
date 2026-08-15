#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
zip_path="${1:-/tmp/wpcomments-ci.zip}"

"$root/tests/build-package.sh" "$zip_path"
entries="$(unzip -Z1 "$zip_path")"

if grep -Eq '^wpcomments/(\.git|\.github|docs|tests|vendor|node_modules)(/|$)' <<<"$entries"; then
    echo 'FAIL: release ZIP contains development-only directories' >&2
    exit 1
fi

if grep -Eq '^wpcomments/(composer\.(json|lock)|package(-lock)?\.json|phpcs(\.release)?\.xml\.dist|phpstan\.neon\.dist|\.distignore|\.env|.*HANDOFF.*)$' <<<"$entries"; then
    echo 'FAIL: release ZIP contains development metadata or handoff material' >&2
    exit 1
fi

for required in wpcomments/wpcomments.php wpcomments/uninstall.php wpcomments/readme.txt; do
    grep -Fxq "$required" <<<"$entries" || {
        printf 'FAIL: release ZIP is missing %s\n' "$required" >&2
        exit 1
    }
done

echo 'PASS: release ZIP contains runtime files only'
