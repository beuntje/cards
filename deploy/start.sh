#!/bin/sh
set -eu

cd -- "$(dirname -- "$0")/.."
./deploy/setup.sh
docker compose up -d --build --wait --wait-timeout 180
docker compose exec -T --user www-data cards-webserver php bin/migrate.php
docker compose ps
