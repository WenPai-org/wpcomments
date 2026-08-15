#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mode="${SITE_MODE:-single}"
wp_version="${WP_VERSION:-5.9}"
wp_image="${WP_IMAGE:-wordpress:5.9-php8.0-apache}"
cli_image="${CLI_IMAGE:-wordpress:cli-php8.0}"
project="wpcomments_migration_${GITHUB_RUN_ID:-local}_${mode}_${RANDOM}"
database="wpcomments_test_${RANDOM}_${RANDOM}"
override="$(mktemp)"
export WPCOMMENTS_HOST_PORT="${WPCOMMENTS_HOST_PORT:-$((20000 + RANDOM % 20000))}"

cleanup() {
    docker compose -p "$project" -f "$root/tests/docker-compose.yml" -f "$override" down -v --remove-orphans >/dev/null 2>&1 || true
    rm -f "$override"
}
trap cleanup EXIT

cat >"$override" <<YAML
services:
  db:
    environment:
      MARIADB_DATABASE: ${database}
  wordpress:
    image: ${wp_image}
    environment:
      WORDPRESS_DB_NAME: ${database}
  cli:
    image: ${cli_image}
    environment:
      WORDPRESS_DB_NAME: ${database}
      WPCOMMENTS_DESTRUCTIVE_TESTS: "1"
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
actual_wp_version="$("${compose[@]}" run --rm cli wp core version --allow-root)"
[[ "$actual_wp_version" == "$wp_version" || "$actual_wp_version" == "$wp_version".* ]] || { echo "FAIL: expected WP $wp_version, got $actual_wp_version" >&2; exit 1; }

if [[ "$mode" == multisite ]]; then
    "${compose[@]}" run --rm cli sh -eu -c "
wp core multisite-install --url=http://localhost:${WPCOMMENTS_HOST_PORT} --title='WPComments migration' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
wp site create --slug=child --title=Child --email=child@example.invalid --allow-root
wp plugin activate wpcomments --network --allow-root
WPCOMMENTS_DESTRUCTIVE_TESTS=1 wp eval-file wp-content/plugins/wpcomments/tests/wp-migration-smoke.php --allow-root
"
else
    "${compose[@]}" run --rm cli sh -eu -c "
wp core install --url=http://localhost:${WPCOMMENTS_HOST_PORT} --title='WPComments migration' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
wp plugin activate wpcomments --allow-root
WPCOMMENTS_DESTRUCTIVE_TESTS=1 wp eval-file wp-content/plugins/wpcomments/tests/wp-migration-smoke.php --allow-root
"
fi
printf 'PASS: migration runner; mode=%s WordPress=%s
' "$mode" "$actual_wp_version"
