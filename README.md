# Cards

Open-source loyalty card manager — store all your membership and loyalty cards in one place.

🔗 **Demo:** [cards.beun.be](https://cards.beun.be)

## Features

- **Barcode scanning** — add cards by scanning with your camera, uploading a screenshot, or manual entry
- **Multiple barcode formats** — EAN-13, EAN-8, UPC-A, UPC-E, Code 128, ITF, Codabar, QR codes
- **Brand logos** — search and attach logos via logo.dev API
- **Custom card colors** — pick a background color or auto-detect from logo
- **Favorites** — mark cards as favorite and filter by them
- **Search & sort** — find cards by name, sort by name, most used, nearby, or date added
- **Usage tracking** — records when and where you use a card (GPS coordinates)
- **Nearby sort** — sorts cards by usage frequency near your current location
- **Fullscreen barcode** — tap a barcode to show it fullscreen at the register
- **PWA support** — installable as a Progressive Web App

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Runtime | PHP 8.5 (Apache) |
| Database | MySQL 8.4 |
| Templating | Twig 3 |
| Frontend | Vanilla JS + CSS |
| Auth | JWT (firebase/php-jwt) |
| Barcode generation | picqer/php-barcode-generator, chillerlan/php-qrcode |
| Barcode scanning | html5-qrcode (ZXing-based) |
| Environment | vlucas/phpdotenv |
| Local development and deployment | Docker Compose with ModSecurity |

## Requirements

- Docker Engine with Docker Compose
- Python 3 for volume and `.env` provisioning

## Installation

```bash
git clone https://github.com/beuntje/cards.git
cd cards
./deploy/start.sh
```

This creates `.env` with generated database/JWT secrets, provisions the
project-owned data volumes, starts PHP 8.5, MySQL 8.4 and ModSecurity, then
applies the database schema. `.env` is not overwritten if it already exists.
The app is available at `http://127.0.0.1:8102`; configure `HTTP_PORT`,
`APP_URL`, and `APP_ENV` in `.env` as needed. Only the ModSecurity service
publishes a host port, bound to loopback.

Useful commands:
```bash
./deploy/check.sh
./docker/database/connect.sh
./deploy/restart-containers.sh
./deploy/update-containers.sh
docker compose logs --tail=50 cards-webserver cards-modsecurity
docker compose down
```

The database helper automatically authenticates as the application user inside
the running database container, without putting the password in arguments.
It also accepts MySQL options and piped SQL:

```sh
./docker/database/connect.sh --execute='SELECT 1'
printf 'SELECT 1;\n' | ./docker/database/connect.sh --batch
```

The app uses the `cards` project name and the fixed services
`cards-webserver`, `cards-modsecurity`, and `cards-database`. MySQL is not
published to the host. For production, set `APP_URL` to the HTTPS site URL,
`APP_ENV=production`, and choose a suitable `HTTP_PORT` for the existing proxy.

Persistent database and logo data live under `data/mysql/` and `data/logos/`,
backed by the external volumes `cards-data-mysql` and `cards-data-logos`.
Container recreation does not remove these volumes. Do not remove the volumes
or edit a running MySQL data directory when updating the containers.
On first database initialization, MySQL writes its generated root password to
the database logs; treat those logs as sensitive.

## Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name |
| `APP_ENV` | Environment (`production` or `development`) |
| `APP_URL` | Base URL of the application |
| `HTTP_PORT` | Loopback port published by ModSecurity |
| `DB_NAME` | MySQL database name |
| `DB_USER` | MySQL application username |
| `DB_PASSWORD` | MySQL application password |
| `JWT_SECRET` | Secret key for JWT token signing |
| `LOGO_DEV_TOKEN` | Optional logo.dev API public key |
| `LOGO_DEV_SECRET` | Optional logo.dev API secret key |

## Project Structure

```
docker/            → PHP/Apache, MySQL and ModSecurity build definitions
deploy/            → Setup, start, check and container maintenance scripts
web/public/        → Webroot (index.php, assets, service worker)
web/src/           → PHP classes (PSR-4: Cards\)
web/templates/     → Twig templates
web/vendor/        → Composer dependencies (generated in webserver container)
data/              → Persistent MySQL and logo storage (gitignored)
```

## License

[GPL-3.0](LICENSE)
