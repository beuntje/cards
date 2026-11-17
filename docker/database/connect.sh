#!/bin/sh
set -eu

cd -- "$(dirname -- "$0")/../.."

no_tty=true
if [ -t 0 ]; then
    no_tty=false
fi

exec docker compose exec --no-TTY="$no_tty" cards-database sh -c \
    'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql --user="$MYSQL_USER" --database="$MYSQL_DATABASE" "$@"' \
    sh "$@"
