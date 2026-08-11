#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
project="wpcomments_e2e_${GITHUB_RUN_ID:-local}_${RANDOM}"
override="$(mktemp)"
export WPCOMMENTS_HOST_PORT="${WPCOMMENTS_HOST_PORT:-$((20000 + RANDOM % 20000))}"
cleanup() {
    docker compose -p "$project" -f "$root/tests/docker-compose.yml" -f "$override" down -v --remove-orphans >/dev/null 2>&1 || true
    rm -f "$override"
}
trap cleanup EXIT
cat >"$override" <<YAML
services:
  wordpress:
    image: wordpress:7.0.2-php8.3-apache
  cli:
    image: wordpress:cli-php8.3
YAML
compose=(docker compose -p "$project" -f "$root/tests/docker-compose.yml" -f "$override")
"${compose[@]}" up -d db wordpress
"${compose[@]}" run --rm cli sh -eu -c '
for attempt in $(seq 1 90); do
    test -f /var/www/html/wp-config.php && test -f /var/www/html/wp-includes/version.php && exit 0
    sleep 2
done
exit 1
'
"${compose[@]}" run --rm cli wp core install --url="http://127.0.0.1:${WPCOMMENTS_HOST_PORT}" --title='WPComments E2E' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
"${compose[@]}" run --rm cli wp plugin activate wpcomments --allow-root
for option in wpcomments_enable_validation wpcomments_enable_private_comments wpcomments_enable_comment_limiter wpcomments_enable_report_comments wpcomments_enable_role_badge wpcomments_enable_sticky_comments wpcomments_enable_email_notification; do
    "${compose[@]}" run --rm cli wp option update "$option" 1 --allow-root >/dev/null
done
"${compose[@]}" run --rm cli wp option update wpcomments_comment_limiter_options '{"minimum_characters":5,"maximum_characters":500,"show_counter":true,"enable_admin_feature":"yes"}' --format=json --allow-root >/dev/null
post_id="$("${compose[@]}" run --rm cli wp post create --post_type=post --post_status=publish --post_title='E2E Comments' --post_content='E2E body' --comment_status=open --porcelain --allow-root)"
comment_id="$("${compose[@]}" run --rm cli wp comment create --comment_post_ID="$post_id" --comment_content='Approved administrator comment' --user_id=1 --comment_approved=1 --porcelain --allow-root)"

cd "$root/tests/e2e"
npm ci --ignore-scripts --no-audit --no-fund
npx playwright install chromium >/dev/null
BASE_URL="http://127.0.0.1:${WPCOMMENTS_HOST_PORT}" POST_ID="$post_id" node comment-flows.cjs

latest_id="$("${compose[@]}" run --rm cli wp db query 'SELECT MAX(comment_ID) FROM wp_comments' --skip-column-names --allow-root)"
[[ "$("${compose[@]}" run --rm cli wp comment meta get "$latest_id" wpcomments_private --allow-root)" == 1 ]] || { echo 'FAIL: private meta missing' >&2; exit 1; }
[[ "$("${compose[@]}" run --rm cli wp comment meta get "$latest_id" wpcomments_subscribe_to_comment --allow-root)" == on ]] || { echo 'FAIL: subscription meta missing' >&2; exit 1; }
[[ "$("${compose[@]}" run --rm cli wp comment get "$latest_id" --field=comment_approved --allow-root)" == 0 ]] || { echo 'FAIL: private comment was approved' >&2; exit 1; }
[[ "$("${compose[@]}" run --rm cli wp comment meta get "$comment_id" wpcomments_sticky --allow-root)" == 1 ]] || { echo 'FAIL: sticky meta missing' >&2; exit 1; }
reports="$("${compose[@]}" run --rm cli wp db query 'SELECT COUNT(*) FROM wp_wpcomments_reports' --skip-column-names --allow-root)"
(( reports >= 1 )) || { echo 'FAIL: report row missing' >&2; exit 1; }
printf 'PASS: E2E database assertions; private=%s sticky=%s reports=%s
' "$latest_id" "$comment_id" "$reports"
