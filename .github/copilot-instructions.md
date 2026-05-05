# Cards — Copilot Instructions

## Project overview
Open Source Loyalty Card Manager. Users can store, manage, and scan loyalty/membership cards.
Live domain: **https://card.beun.be**
Local dev: **https://cards.lndo.site** (Lando)

## Tech stack
| Layer | Choice |
|---|---|
| Runtime | PHP 8.3 (Apache via Lando LAMP recipe) |
| Database | MySQL 8.0 |
| Frontend | Vanilla JS + plain CSS (no frameworks) |
| Package manager | Composer 2 (`lando composer`) |
| Auth | JWT (firebase/php-jwt), token stored in cookies |
| Templates | Twig 3 |
| Barcode generation | picqer/php-barcode-generator, chillerlan/php-qrcode |
| Barcode scanning | html5-qrcode (ZXing-based, client-side JS) |

## Project structure
- `public_html/` — webroot (index.php, .htaccess, assets/, service worker)
- `src/` — PHP classes (PSR-4: Cards\)
- `templates/` — Twig templates
- `vendor/` — Composer dependencies (gitignored)

## Key conventions
- Routing: all requests routed through `public_html/index.php` via .htaccess
- Templates: Twig 3, base layout in `templates/base.html.twig`
- Env vars: loaded via vlucas/phpdotenv from `.env`
- Auth: JWT tokens in cookies, 1 year expiry, auto-refresh after 1 month
- PWA: service worker + manifest.json in public_html/

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
APP_NAME, APP_ENV, APP_URL, TWIG_PATH, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, JWT_SECRET, LOGO_DEV_TOKEN, LOGO_DEV_SECRET

## Lando commands
lando start / stop / rebuild -y
lando composer <args>
lando info

## Changelog & Git
- Always update CHANGELOG.md in every commit
- Commit messages: single short one-liner, no body
- Tag before pushing: features → +0.1.0, fixes → +0.0.1, ask when unsure
- Push: git push && git push --tags
- Changelog version links use compare URLs
