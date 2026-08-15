#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output="${1:-/tmp/wpcomments-plugin-check.csv}"
project="wpcomments_plugin_check_${GITHUB_RUN_ID:-local}_${RANDOM}"
stage="$(mktemp -d)"
override="$(mktemp)"
zip_path="$stage/wpcomments.zip"
export WPCOMMENTS_HOST_PORT="${WPCOMMENTS_HOST_PORT:-$((20000 + RANDOM % 20000))}"
cleanup() {
    docker compose -p "$project" -f "$root/tests/docker-compose.yml" -f "$override" down -v --remove-orphans >/dev/null 2>&1 || true
    rm -rf "$stage" "$override"
}
trap cleanup EXIT

"$root/tests/build-package.sh" "$zip_path" >/dev/null
unzip -q "$zip_path" -d "$stage/release"
cat >"$override" <<YAML
services:
  wordpress:
    image: wordpress:7.0.2-php8.3-apache
    volumes:
      - wordpress-data:/var/www/html
      - ${stage}/release/wpcomments:/var/www/html/wp-content/plugins/wpcomments:ro
  cli:
    image: wordpress:cli-php8.3
    volumes:
      - wordpress-data:/var/www/html
      - ${stage}/release/wpcomments:/var/www/html/wp-content/plugins/wpcomments:ro
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
"${compose[@]}" run --rm cli sh -eu -c "
wp core install --url=http://localhost:${WPCOMMENTS_HOST_PORT} --title='Plugin Check' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
wp plugin install https://downloads.wordpress.org/plugin/plugin-check.2.0.0.zip --activate --allow-root
wp plugin activate wpcomments --allow-root
"
set +e
"${compose[@]}" run --rm cli wp plugin check wpcomments --allow-root --format=csv --fields=file,line,column,type,code,message,docs >"$output"
rc=$?
set -e
printf 'Plugin Check exit=%d output=%s
' "$rc" "$output"
exit "$rc"
