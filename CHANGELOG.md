# Changelog

All notable changes will be documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.0.0] — 2026-05-06

### Added
- JWT authentication (login, register, logout, auto-refresh with secure cookies)
- Card CRUD (create, read, update, delete)
- Barcode scanning via webcam and image upload (html5-qrcode / ZXing-based)
- Support for EAN-13, EAN-8, CODE-128, CODE-39, ITF, CODABAR, QR codes
- Server-side barcode/QR rendering (picqer/php-barcode-generator, chillerlan/php-qrcode)
- Brand logo search via logo.dev API with auto-search on card name input
- Logo upload (file) and logo URL input options
- Logo optimization: resized to max 256×256, saved as WebP
- Background color auto-detected from logo, random color when no logo set
- Manual background color picker (edit page)
- Favorite cards ("Always show on top") with smart sorting
- Card search (instant client-side filtering)
- Sort by: smart (nearby + usage), name, usage count, location, created
- Usage tracking with GPS coordinates per card view
- Geolocation prompt on card view
- PWA support (manifest, service worker, offline access, icon set)
- Form validation with visual feedback (highlight valid fields, disable submit until ready)
- Fullscreen barcode overlay on tap
- Wake Lock API to keep screen on during card view
- SSRF protection (HTTPS-only logo downloads)
- Mobile-friendly responsive design

[Unreleased]: https://github.com/beuntje/cards/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/beuntje/cards/releases/tag/v1.0.0
