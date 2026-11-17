#!/bin/sh
set -eu

cd -- "$(dirname -- "$0")/.."
docker compose config --quiet
docker compose exec -T --user app-installer cards-webserver composer validate --no-check-publish
docker compose exec -T --user app-installer cards-webserver composer check-platform-reqs --no-dev
docker compose exec -T cards-webserver apache2ctl -t
docker compose exec -T cards-webserver sh -c 'find src public bin -name "*.php" -exec php -l {} \;'
docker compose ps
