# Cards — Copilot Instructions

## Project overview
Open Source Loyalty Card Manager. Users can store, manage, and scan loyalty/membership cards.
Live domain: **https://card.beun.be**
Local dev: **http://127.0.0.1:8102** (Docker Compose)

## Tech stack
| Layer | Choice |
|---|---|
| Runtime | PHP 8.5 (Apache) |
| Database | MySQL 8.4 |
| Frontend | Vanilla JS + plain CSS (no frameworks) |
| Package manager | Composer 2 (installed at webserver startup) |
| Auth | JWT (firebase/php-jwt), token stored in cookies |
| Templates | Twig 3 |
| Barcode generation | picqer/php-barcode-generator, chillerlan/php-qrcode |
| Barcode scanning | html5-qrcode (ZXing-based, client-side JS) |

## Project structure
- `web/public/` — webroot (index.php, assets/, service worker)
- `web/src/` — PHP classes (PSR-4: Cards\)
- `web/templates/` — Twig templates
- `web/vendor/` — Composer dependencies (generated in container)
- `docker/` — PHP/Apache, MySQL and ModSecurity builds
- `deploy/` — Compose setup and maintenance scripts
- `data/` — persistent database and logo storage (gitignored)

## Key conventions
- Routing: all requests routed through `web/public/index.php` via .htaccess
- Templates: Twig 3, base layout in `web/templates/base.html.twig`
- Env vars: loaded via vlucas/phpdotenv from `.env`
- Auth: JWT tokens in cookies, 1 year expiry, auto-refresh after 1 month
- PWA: service worker + manifest.json in `web/public/`
- Runtime: Docker Compose only; PHP 8.5, MySQL 8.4, and ModSecurity
- Persistent volumes: `cards-data-mysql` and `cards-data-logos`

## Database schema
- `users`: id, email, password
- `cards`: id, user_id, name, number, barcode_type, favorite, logo, color, created_at, updated_at
- `card_usage`: id, card_id, used_at, latitude, longitude

## Features
- Login required (JWT auth)
- CRUD operations on loyalty cards
- Search cards by name (client-side JS filter)
- Sort cards by name, usage count, nearby location, created_at
- Favorite cards with filter
- Add cards via camera scan, screenshot upload, or manual entry (html5-qrcode)
- Supports EAN-13, EAN-8, UPC-A, UPC-E, Code 128, ITF, Codabar, QR codes
- Usage tracking with GPS coordinates on card view
- Brand logo search via logo.dev API
- Background color picker (or auto-detect from logo)
- Fullscreen barcode overlay on tap
- PWA installable (manifest + service worker)

## Environment variables
APP_NAME, APP_ENV, APP_URL, HTTP_PORT, DB_USER, DB_PASSWORD, DB_NAME, JWT_SECRET, LOGO_DEV_TOKEN, LOGO_DEV_SECRET

## Docker Compose commands
`./deploy/start.sh` / `./deploy/check.sh` / `./deploy/restart-containers.sh`
`./deploy/update-containers.sh`

## Changelog & Git
- Always update CHANGELOG.md in every commit
- Commit messages: single short one-liner, no body
- Tag before pushing: features → +0.1.0, fixes → +0.0.1, ask when unsure
- Push: git push && git push --tags
- Changelog version links use compare URLs
