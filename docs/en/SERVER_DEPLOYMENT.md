# AUB — Server Deployment

## What is deployed here

This document covers **AUB_admin** production (web core). Flutter (`AUB_app`) is a separate GitHub repository and is **not** deployed to `/var/www/aub`.

| Item | Value |
|------|-------|
| GitHub (source of truth for Tech Lead) | `https://github.com/Owiiiii1/AUB_admin` |
| Server IP | `178.156.234.23` |
| Linux user | `deploy` |
| Project path | `/var/www/aub` |
| Git on server | **Not a git repository** (2026-09-07) |
| Web root | `/var/www/aub/public` |
| Domain | `https://aub.owlsolutions.net` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |
| PHP | 8.3.6 |
| Node.js | 20.20.0 |
| MySQL | 8.0.46 |
| Nginx | 1.24.0 (Ubuntu) |

Nginx: `/etc/nginx/sites-available/aub.owlsolutions.net`  
`server_name aub.owlsolutions.net;`  
`root /var/www/aub/public;`

## GitHub vs production

- Tech Lead reviews **GitHub `main`**.
- Production is a **file tree**. There is no `git pull` on the server today.
- After Cursor pushes docs or code to GitHub, copying files to `/var/www/aub` is a **separate ops step** unless the task forbids touching production (docs-only tasks must not change server state).
- Do not initialize git on production unless Tech Lead opens that task.

## Standard commands (when deploying AUB_admin files)

Run from `/var/www/aub` after files are in place:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force   # only if new migrations shipped

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Docs-only updates do not require migrate or npm build.

## Development (local clone of AUB_admin)

```bash
composer install
npm install
npm run dev
php artisan migrate
php artisan serve            # local only
```

## Permissions

Web user: `www-data`  
Owner: `deploy:www-data`

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
php artisan --version
composer show owlsolutions/custom-admin-kit
php artisan route:list          # expect 84 web routes; no /api Flutter routes
php artisan migrate:status      # 37 files, all Ran, batches 1–29
php artisan owl-admin:smoke --preset=admin
php artisan owl-admin:doctor --preset=admin
tail -80 storage/logs/laravel.log

curl -I https://aub.owlsolutions.net/
curl -I https://aub.owlsolutions.net/owl-admin/health
curl -I https://aub.owlsolutions.net/customers
curl -I https://aub.owlsolutions.net/dashboard
```

| URL | Expected |
|-----|----------|
| `/` | 200 (login) |
| `/owl-admin/health` | 200 JSON |
| `/customers` (guest) | 302 → `/` |
| `/dashboard` (guest) | 302 → `/` |

## Nginx reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Environment

`/var/www/aub/.env` is not in git.

Required keys (values never documented): `APP_NAME`, `APP_KEY`, `APP_URL`, `DB_*`.

**Never expose secret values.**

## Kit commands (already installed)

Do not re-run install unless intentional.

```bash
php artisan owl-admin:make-admin --email=admin@admin.com --password=admin
php artisan owl-admin:doctor --preset=admin
php artisan owl-admin:smoke --preset=admin
```

Test admin email is for development only.

## Post-deploy checklist

- [ ] Intended files copied (not a git pull today)
- [ ] `composer install` if PHP deps changed
- [ ] `npm run build` if frontend changed
- [ ] Migrations only if new files shipped
- [ ] Caches rebuilt
- [ ] HTTP checks
- [ ] Laravel log clean of new errors
