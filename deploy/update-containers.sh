#!/bin/sh
set -eu

cd -- "$(dirname -- "$0")/.."
./deploy/setup.sh
docker compose build --pull
docker compose up -d --wait --wait-timeout 180
docker compose ps
