# Cards

Open-source loyalty card manager — store all your membership and loyalty cards in one place.

🔗 **Demo:** [card.beun.be](https://card.beun.be)

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
| Runtime | PHP 8.3 (Apache) |
| Database | MySQL 8.0 |
| Templating | Twig 3 |
| Frontend | Vanilla JS + CSS |
| Auth | JWT (firebase/php-jwt) |
| Barcode generation | picqer/php-barcode-generator, chillerlan/php-qrcode |
| Barcode scanning | html5-qrcode (ZXing-based) |
| Environment | vlucas/phpdotenv |
| Local dev | Lando (LAMP recipe) |

## Requirements

- PHP 8.3+
- MySQL 8.0+
- Composer 2
- Apache with mod_rewrite

## Installation

```bash
git clone https://github.com/beuntje/cards.git
cd cards
cp .env.example .env
composer install
```

Edit `.env` with your database credentials and a random `JWT_SECRET`.

### Database Setup

Create the MySQL database and run the schema:

```sql
CREATE DATABASE cards;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(255) NOT NULL,
    barcode_type VARCHAR(50),
    favorite TINYINT(1) DEFAULT 0,
    logo VARCHAR(255),
    color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE card_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    FOREIGN KEY (card_id) REFERENCES cards(id)
);
```

### Local Development with Lando

```bash
lando start
lando composer install
```

The app will be available at `https://cards.lndo.site`.

## Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name |
| `APP_ENV` | Environment (`production` or `development`) |
| `APP_URL` | Base URL of the application |
| `TWIG_PATH` | Path to Twig templates directory |
| `DB_HOST` | Database host |
| `DB_USER` | Database username |
| `DB_PASSWORD` | Database password |
| `DB_NAME` | Database name |
| `JWT_SECRET` | Secret key for JWT token signing |
| `LOGO_DEV_TOKEN` | logo.dev API public key |
| `LOGO_DEV_SECRET` | logo.dev API secret key |

## Project Structure

```
public_html/       → Webroot (index.php, assets, service worker)
src/               → PHP classes (PSR-4: Cards\)
templates/         → Twig templates
vendor/            → Composer dependencies (gitignored)
```

## License

[GPL-3.0](LICENSE)
