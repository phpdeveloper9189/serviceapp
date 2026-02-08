# Service Delivery App (Cordova-style: HTML/CSS/JS + `api.php`)

This project now uses a simple Cordova-friendly stack:
- Frontend in `www/` (HTML, CSS, JavaScript)
- Backend API in `api.php`
- JSON-file persistence in `data/store.json`

## Project structure

- `www/index.html` – UI shell for admin/user/partner demo actions
- `www/style.css` – basic app styling
- `www/app.js` – fetch-based API calls to `api.php`
- `api.php` – REST-like action API

## Run locally

```bash
php -S 127.0.0.1:8080
```

Then open:
- `http://127.0.0.1:8080/www/index.html`

## API actions (examples)

- `GET /api.php?action=health`
- `POST /api.php?action=signup_user`
- `POST /api.php?action=add_provider`
- `POST /api.php?action=approve_provider`
- `POST /api.php?action=add_category`
- `POST /api.php?action=add_service`
- `POST /api.php?action=add_coupon`
- `POST /api.php?action=create_booking`
- `POST /api.php?action=assign_booking`
- `POST /api.php?action=partner_update_status`
- `GET /api.php?action=revenue_report`

## Cordova integration note

In Cordova, keep `www/` as your app web assets and point `API_BASE` in `www/app.js` to your hosted PHP endpoint.
