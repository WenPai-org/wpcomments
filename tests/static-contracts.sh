#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

fail() {
    printf 'FAIL: %s\n' "$1" >&2
    exit 1
}

grep -q "WPComments_Core::get_instance(\$this->feature_settings)" includes/class-wpcomments-bootstrap.php \
    || fail "bootstrap does not pass effective settings to core"
! grep -q "get_option('wpcomments_settings'" includes/class-wpcomments-core.php \
    || fail "core still reads the obsolete aggregate settings option"

for asset in \
    assets/js/comment-report.js \
    assets/js/comment-limiter.js \
    assets/css/comment-limiter.css \
    assets/comment-author-role-badge.css; do
    test -f "$asset" || fail "missing referenced asset: $asset"
done

grep -q "wpcomments_comment_limiter_options" modules/class-character-limiter-module.php \
    || fail "character limiter reads the wrong option key"

for column in user_ip_hash reason report_date; do
    grep -q "$column" wpcomments.php || fail "activation schema lacks $column"
    grep -q "$column" modules/class-comment-reporting-module.php || fail "reporting module lacks $column"
done

grep -q "wpcomments_delete_data_on_uninstall" uninstall.php \
    || fail "uninstall has no explicit data deletion opt-in"

printf 'PASS: static contracts\n'
