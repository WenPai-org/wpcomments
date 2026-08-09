#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mode="${SITE_MODE:-single}"
wp_version="${WP_VERSION:-7.0}"
wp_image="${WP_IMAGE:-wordpress:php8.3-apache}"
cli_image="${CLI_IMAGE:-wordpress:cli-php8.3}"
project="wpcomments_ci_${GITHUB_RUN_ID:-local}_${GITHUB_RUN_ATTEMPT:-1}_${mode}_${RANDOM}"
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
    image: ${wp_image}
  cli:
    image: ${cli_image}
YAML

compose=(docker compose -p "$project" -f "$root/tests/docker-compose.yml" -f "$override")
"${compose[@]}" up -d db wordpress
"${compose[@]}" run --rm cli sh -eu -c '
for attempt in $(seq 1 90); do
    if test -f /var/www/html/wp-config.php && test -f /var/www/html/wp-includes/version.php; then
        exit 0
    fi
    sleep 2
done
echo "WordPress image did not initialize its shared volume" >&2
exit 1
'

actual_wp_version="$("${compose[@]}" run --rm cli wp core version --allow-root)"
if [[ "$actual_wp_version" != "$wp_version" && "$actual_wp_version" != "$wp_version".* ]]; then
    printf 'Expected WordPress %s, got %s\n' "$wp_version" "$actual_wp_version" >&2
    exit 1
fi

if [[ "$mode" == "multisite" ]]; then
    "${compose[@]}" run --rm cli sh -eu -c "
php -d memory_limit=512M /usr/local/bin/wp core multisite-install --url=http://localhost:${WPCOMMENTS_HOST_PORT} --title='WPComments CI Multisite' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
php -d memory_limit=512M /usr/local/bin/wp site create --slug=child --title='Child' --email=child@example.invalid --allow-root
php -d memory_limit=512M /usr/local/bin/wp plugin activate wpcomments --network --allow-root
php -d memory_limit=512M /usr/local/bin/wp eval-file wp-content/plugins/wpcomments/tests/wp-multisite-smoke.php --allow-root
php -d memory_limit=512M /usr/local/bin/wp core version --allow-root
php -r 'echo PHP_VERSION, PHP_EOL;'
"
else
    "${compose[@]}" run --rm cli sh -eu -c "
php -d memory_limit=512M /usr/local/bin/wp core install --url=http://localhost:${WPCOMMENTS_HOST_PORT} --title='WPComments CI' --admin_user=admin --admin_password=adminpass --admin_email=admin@example.invalid --skip-email --allow-root
php -d memory_limit=512M /usr/local/bin/wp plugin activate wpcomments --allow-root
php -d memory_limit=512M /usr/local/bin/wp eval-file wp-content/plugins/wpcomments/tests/wp-runtime-smoke.php --allow-root
php -d memory_limit=512M /usr/local/bin/wp option update wpcomments_enable_email_notification 1 --allow-root
php -d memory_limit=512M /usr/local/bin/wp eval-file wp-content/plugins/wpcomments/tests/wp-email-smoke.php --allow-root
php -d memory_limit=512M /usr/local/bin/wp option update wpcomments_disable_comments 1 --allow-root
php -d memory_limit=512M /usr/local/bin/wp eval-file wp-content/plugins/wpcomments/tests/wp-disable-smoke.php --allow-root
php -d memory_limit=512M /usr/local/bin/wp core version --allow-root
php -r 'echo PHP_VERSION, PHP_EOL;'
"
fi
