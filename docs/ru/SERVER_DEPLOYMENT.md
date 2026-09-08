# AUB — Развёртывание на сервере

## Что здесь деплоится

Документ про production **AUB_admin** (web-ядро). Flutter (`AUB_app`) — отдельный GitHub-репозиторий и **не** выкладывается в `/var/www/aub`.

| Параметр | Значение |
|----------|----------|
| GitHub (истина для Tech Lead) | `https://github.com/Owiiiii1/AUB_admin` |
| IP | `178.156.234.23` |
| Linux user | `deploy` |
| Путь проекта | `/var/www/aub` |
| Git на сервере | **Не git-репозиторий** (2026-09-07) |
| Web root | `/var/www/aub/public` |
| Домен | `https://aub.owlsolutions.net` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |
| PHP | 8.3.6 |
| Node.js | 20.20.0 |
| MySQL | 8.0.46 |
| Nginx | 1.24.0 (Ubuntu) |

Nginx: `/etc/nginx/sites-available/aub.owlsolutions.net`  
`server_name aub.owlsolutions.net;`  
`root /var/www/aub/public;`

## GitHub vs production

- Tech Lead смотрит **GitHub `main`**.
- Production — **файловое дерево**. `git pull` на сервере сейчас нет.
- **Обязательно:** любое изменение `AUB_admin` (код и docs) после push в `main` сразу копируется в `/var/www/aub`. Задача не закончена, пока файлы не на сервере.
- Не заливать `.env`, `vendor`, `node_modules`, storage runtime, Flutter.
- Не инициализировать git на production, пока Tech Lead не откроет такую задачу.

## Стандартные команды (когда выкладываются файлы AUB_admin)

Из `/var/www/aub` после появления файлов:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force   # только если приехали новые миграции

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Обновление только документации не требует migrate или npm build.

## Разработка (локальный клон AUB_admin)

```bash
composer install
npm install
npm run dev
php artisan migrate
php artisan serve            # только local
```

## Права

Пользователь веб-сервера: `www-data`  
Владелец: `deploy:www-data`

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
php artisan --version
composer show owlsolutions/custom-admin-kit
php artisan route:list --path=api   # 5 routes: health, login, logout, logout-all, me
php artisan migrate:status      # 40 файлов, personal_access_tokens Ran
php artisan owl-admin:smoke --preset=admin
php artisan owl-admin:doctor --preset=admin
tail -80 storage/logs/laravel.log

curl -I https://aub.owlsolutions.net/
curl -I https://aub.owlsolutions.net/owl-admin/health
curl -I https://aub.owlsolutions.net/customers
curl -I https://aub.owlsolutions.net/dashboard
```

| URL | Ожидается |
|-----|-----------|
| `/` | 200 (login) |
| `/owl-admin/health` | 200 JSON |
| `/customers` (гость) | 302 → `/` |
| `/dashboard` (гость) | 302 → `/` |

## Reload Nginx

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Окружение

`/var/www/aub/.env` не в git.

Обязательные ключи (значения не документируются): `APP_NAME`, `APP_KEY`, `APP_URL`, `DB_*`, `AUB_API_TOKEN_EXPIRATION_MINUTES` (default 43200).

**Никогда не раскрывать секреты.** Не заливать `.env` и `.env.testing`.

## Test database (изолированный MySQL)

| Роль | Имя БД |
|------|--------|
| Production-приложение | `aub` |
| Автотесты | `aub_test` |

Тесты используют отдельную MySQL DB (и отдельного test-пользователя с правами только на `aub_test`). Production `aub` никогда не является целью PHPUnit.

Секреты test-подключения — только на сервере в `/var/www/aub/.env.testing`. Шаблон в git: `.env.testing.example`. phpunit.xml принудительно задаёт `DB_DATABASE=aub_test` **без** паролей.

Как Laravel выбирает test-подключение:

- `php artisan test` → PHPUnit → `phpunit.xml` задаёт `APP_ENV=testing` и `DB_DATABASE=aub_test`.
- Если config **не** закэширован, Laravel грузит `.env`, затем `.env.testing`; username/password берутся из `.env.testing`.
- `php artisan … --env=testing` загружает `.env.testing` (проверено: `migrate:status --env=testing` и `db:show --env=testing` показывают database `aub_test`).
- `App\Testing\TestDatabaseGuard` прерывает запуск, если resolved database не строго `aub_test`.

Перед любым прогоном тестов: `php artisan optimize:clear`. **Не** запускать `php artisan test` после `config:cache`.

```bash
php artisan optimize:clear
php artisan test
php artisan migrate:status --env=testing
```

## Команды kit (уже установлено)

Не перезапускать install без намерения.

```bash
php artisan owl-admin:make-admin --email=admin@admin.com --password=admin
php artisan owl-admin:doctor --preset=admin
php artisan owl-admin:smoke --preset=admin
```

Тестовый email администратора — только для разработки.

## Чеклист после деплоя

- [ ] Нужные файлы скопированы (сейчас не git pull)
- [ ] `composer install` если менялись PHP-зависимости
- [ ] `npm run build` если менялся frontend
- [ ] Миграции только если приехали новые файлы
- [ ] Кэши пересобраны
- [ ] HTTP-проверки
- [ ] В логе Laravel нет новых ошибок
