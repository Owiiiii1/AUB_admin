# AUB — Развёртывание на сервере

## Информация о сервере и проекте

| Параметр | Значение |
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

Конфигурация Nginx: `/etc/nginx/sites-available/aub.owlsolutions.net`  
`server_name aub.owlsolutions.net;`  
`root /var/www/aub/public;`

## Стандартные команды развёртывания

Выполнять из `/var/www/aub`:

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

## Команды для разработки

```bash
composer install
npm install
npm run dev          # Vite dev server

php artisan migrate
php artisan serve    # Local dev only
```

## Права доступа

Пользователь веб-сервера: `www-data`  
Владелец проекта: `deploy:www-data`

Безопасная настройка прав (исключает `node_modules` и `vendor`):

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

## Диагностика

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

Ожидаемые HTTP-ответы:

| URL | Ожидается |
|-----|----------|
| `/` | 200 (login page) |
| `/owl-admin/health` | 200 (JSON health) |
| `/customers` (guest) | 302 → `/` |
| `/dashboard` (guest) | 302 → `/` |

## Перезагрузка Nginx

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Конфигурация окружения

Переменные окружения находятся в `/var/www/aub/.env` (не коммитятся в git).

Обязательные ключи (значения здесь не показаны):

- `APP_NAME`, `APP_KEY`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Опциональные ключи kit:

- `OWL_ADMIN_BRAND`, `OWL_ADMIN_LOGO`
- `OWL_ADMIN_ROUTE_PREFIX`, `OWL_ADMIN_LOGIN_PATH`
- `OWL_ADMIN_EMAIL`, `OWL_ADMIN_PASSWORD` (for `--seed`)

**Никогда не раскрывать секретные значения в документации или логах.**

## Справочник команд admin kit

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

## Чеклист после развёртывания

- [ ] `composer install` выполнен успешно
- [ ] `npm run build` выполнен успешно
- [ ] Миграции применены
- [ ] Config/route/view закэшированы
- [ ] `owl-admin:smoke` пройден
- [ ] HTTP-проверки возвращают ожидаемые коды статуса
- [ ] Нет ошибок в `storage/logs/laravel.log`
