# AUB — Server Deployment

## Server and project info

| Item | Value |
|------|-------|
| Server IP | `178.156.234.23` |
| Linux user | `deploy` |
| Project path | `/var/www/aub` |
| Web root | `/var/www/aub/public` |
| Domain | `https://aub.owlsolutions.net` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |
| PHP | 8.3.6 |
| Node.js | 20.20.0 |
| MySQL | 8.0.46 |
| Nginx | 1.24.0 (Ubuntu) |

Nginx config: `/etc/nginx/sites-available/aub.owlsolutions.net`  
`server_name aub.owlsolutions.net;`  
`root /var/www/aub/public;`

## Standard deployment commands

Run from `/var/www/aub`:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Database
php artisan migrate --force

# Cache (production)
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Development commands

```bash
composer install
npm install
npm run dev          # Vite dev server

php artisan migrate
php artisan serve    # Local dev only
```

## Permissions

Web server user: `www-data`  
Project owner: `deploy:www-data`

Safe permission setup (excludes `node_modules` and `vendor`):

```bash
sudo chown -R deploy:www-data /var/www/aub

sudo find /var/www/aub \
  -path /var/www/aub/node_modules -prune -o \
  -path /var/www/aub/vendor -prune -o \
  -type f -exec chmod 664 {} \;

sudo find /var/www/aub \
  -path /var/www/aub/node_modules -prune -o \
  -path /var/www/aub/vendor -prune -o \
  -type d -exec chmod 775 {} \;

sudo chmod -R ug+rwx /var/www/aub/storage /var/www/aub/bootstrap/cache
```

## Diagnostics

```bash
# Versions
php artisan --version
composer show owlsolutions/custom-admin-kit

# Routes and migrations
php artisan route:list
php artisan migrate:status

# Admin kit health
php artisan owl-admin:smoke --preset=admin
php artisan owl-admin:doctor --preset=admin

# Logs
tail -80 storage/logs/laravel.log

# Frontend build
cat public/build/manifest.json

# HTTP checks
curl -I https://aub.owlsolutions.net/
curl -I https://aub.owlsolutions.net/owl-admin/health
curl -I https://aub.owlsolutions.net/customers
curl -I https://aub.owlsolutions.net/dashboard
```

Expected HTTP responses:

| URL | Expected |
|-----|----------|
| `/` | 200 (login page) |
| `/owl-admin/health` | 200 (JSON health) |
| `/customers` (guest) | 302 → `/` |
| `/dashboard` (guest) | 302 → `/` |

## Nginx reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Environment configuration

Environment variables are in `/var/www/aub/.env` (not committed to git).

Required keys (values not shown here):

- `APP_NAME`, `APP_KEY`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Optional kit keys:

- `OWL_ADMIN_BRAND`, `OWL_ADMIN_LOGO`
- `OWL_ADMIN_ROUTE_PREFIX`, `OWL_ADMIN_LOGIN_PATH`
- `OWL_ADMIN_EMAIL`, `OWL_ADMIN_PASSWORD` (for `--seed`)

**Never expose secret values in documentation or logs.**

## Admin kit commands reference

```bash
# Install (already done — do not re-run unless intentional)
php artisan owl-admin:install --preset=admin --backup --migrate --no-smoke
php artisan owl-admin:frontend-setup --preset=admin --backup --install-npm --run-build

# Create admin user
php artisan owl-admin:make-admin --email=admin@admin.com --password=admin

# Diagnostics
php artisan owl-admin:doctor --preset=admin
php artisan owl-admin:smoke --preset=admin
```

## Post-deploy checklist

- [ ] `composer install` succeeded
- [ ] `npm run build` succeeded
- [ ] Migrations applied
- [ ] Config/route/view cached
- [ ] `owl-admin:smoke` passed
- [ ] HTTP checks return expected status codes
- [ ] No errors in `storage/logs/laravel.log`
