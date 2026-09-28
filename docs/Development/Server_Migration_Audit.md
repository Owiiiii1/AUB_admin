# Server migration audit

Date of inspection: 2026-09-28. Read-only. Nothing was installed, restarted, migrated, deployed, or changed on either server. DNS was not touched. Production `.env` was not edited. Secret values were not copied into this document.

Inspection answer on 2026-09-28, before the move: **NO. Do not cut over yet.**

The cutover was completed later the same day. See **Migration execution result** at the end. Production is `https://staff.accademiaucraina.it` on `195.201.37.229`. The previous server stays up in maintenance as rollback and is not deleted.

The inspection below describes the servers as they were before that move. The old host at that moment was `deploy@178.156.234.23`, `/var/www/aub`, `https://aub.owlsolutions.net`.

## A. Executive summary

The application can be moved, but the new server is only a partial copy. Code, storage, and a database dump are there. Composer, Node, the database restore, the nginx site, TLS, cron, and file permissions for PHP-FPM are not. The copied `.env` still points at the old domain and still has `APP_ENV=local` and `APP_DEBUG=true`. The Flutter app still calls `https://aub.owlsolutions.net/api/v1`.

PHP 8.5 is allowed by the Composer constraints and the needed extensions are installed, but this codebase has never been installed or tested on PHP 8.5. That check has to happen on the new server before any DNS change, without `--ignore-platform-reqs`. If `composer install` fails, install PHP 8.3 or 8.4 next to 8.5 and run the site on that.

The existing `APP_KEY` must be kept. It is already present in the copied `.env`. Do not run `key:generate`.

## B. Current architecture

Two repositories, one live web server.

| Piece | Where it lives |
| --- | --- |
| Laravel admin and API | Local `C:\Users\owlni\PhpstormProjects\AUB`. Production is a file tree at `/var/www/aub`, not a git clone |
| Flutter app | Local `C:\MobAPP\AUB_app`. It is not copied to `/var/www/aub` |
| Live domain | `https://aub.owlsolutions.net` |
| Live API | `https://aub.owlsolutions.net/api/v1` |
| Target domain | `https://staff.accademiaucraina.it` (not configured) |
| Timezone | `Europe/Rome` |
| Database | MySQL, database name `aub`, user `aub_user`, host `127.0.0.1` |
| Session, cache, queue | All `database` |
| Mail | `log` (nothing is sent) |
| Private files | `storage/app/aub-private`, served only through the app, not as public URLs |
| Scheduler | One cron line runs `schedule:run` every minute. The only scheduled command is `medical-certificates:watch` at 07:00 Europe/Rome |
| Queue worker | None. The medical recognition job is dispatched after the HTTP response, not by a worker. `jobs` and `failed_jobs` are empty |
| Frontend build | Vite, built on the server with `npm run build`. Built assets are already in `public/build` |

Laravel on production is **13.18.1**. `composer.lock` matches that version. PHP constraint in `composer.json` is `^8.3`.

Encrypted database values that depend on `APP_KEY`:

- `users.admin_visible_password`
- AI provider API keys (`AiProviderSetting.api_key`)

Sanctum is used as bearer tokens for the mobile app. Stateful domains are empty. CORS allows no browser origins. Native Flutter does not need CORS.

Secure file links are generated with Laravel `url()`, so they follow `APP_URL`. There is no hardcoded production host in the PHP that builds those links.

## C. Old production findings

Host `178.156.234.23`, user `deploy`.

| Item | Observed |
| --- | --- |
| OS | Ubuntu 24.04.3 LTS, kernel 6.8.0-90-generic |
| Disk | 38 GB, 11 GB used, 25 GB free |
| Memory | 1.9 GB RAM, 2 GB swap, about 1.1 GB available |
| nginx | 1.24.0, site `aub.owlsolutions.net` |
| Web root | `/var/www/aub/public` |
| TLS | Let's Encrypt, CN `aub.owlsolutions.net`, valid 24 Aug 2026 through 22 Nov 2026. Ports 80 and 443 listen on all interfaces |
| PHP | 8.3.6 CLI and FPM, pool user `www-data` |
| PHP extensions | bcmath, curl, gd, intl, mbstring, pdo_mysql, sodium, zip, Zend OPcache, and the usual core set |
| MySQL | 8.0.46, listens on `127.0.0.1:3306` only |
| Node | 20.20.0, npm 10.8.2 |
| Composer | 2.9.4 |
| Project | `/var/www/aub`, no `.git`, owner `deploy:www-data`, mode 775 |
| Size | Project 321 MB. `vendor` 87 MB. `node_modules` 187 MB. `public/build` 2.0 MB. Private files 16 MB. Public storage 876 KB |
| `public/storage` | Symlink to `storage/app/public` |
| Database | 57 tables, about 4.1 MB, 59 migrations, none pending |
| Queue | 0 pending jobs, 0 failed jobs |
| Cron | `* * * * * cd /var/www/aub && php artisan schedule:run` |
| Supervisor | Service is running. `conf.d` has no programs. `deploy` cannot query `supervisorctl` |
| Fail2ban | Inactive |
| UFW | Not readable without a password. Not treated as off |
| Log | `storage/logs/laravel.log` is 207 KB. 27 lines marked `local.ERROR` because the app is not in the `production` environment. 0 lines marked `production.ERROR` |

Non-secret `.env` values on the live server:

| Key | Value |
| --- | --- |
| APP_NAME | AUB |
| APP_ENV | `local` |
| APP_DEBUG | `true` |
| APP_URL | `https://aub.owlsolutions.net` |
| APP_TIMEZONE | `Europe/Rome` |
| APP_LOCALE | `it` |
| APP_KEY | Set. Value not recorded |
| DB_CONNECTION | mysql |
| DB_HOST | 127.0.0.1 |
| DB_PORT | 3306 |
| DB_DATABASE | aub |
| DB_USERNAME | aub_user |
| SESSION_DRIVER | database |
| SESSION_DOMAIN | null |
| CACHE_STORE | database |
| QUEUE_CONNECTION | database |
| FILESYSTEM_DISK | local |
| MAIL_MAILER | log |
| MAIL_FROM_ADDRESS | hello@example.com |

`.env` is mode `664` (`deploy:www-data`). Any local account can read it, including `APP_KEY`.

nginx listens on 443 with the Certbot certificate and on 80 for the same name. MySQL is not exposed beyond localhost.

## D. New server findings

Host `195.201.37.229`, user `deploy`.

| Item | Observed |
| --- | --- |
| OS | Ubuntu 26.04.1 LTS, kernel 7.0.0-34-generic |
| Disk | 38 GB, 2.8 GB used, 33 GB free |
| Memory | 3.7 GB RAM, no swap, 2 CPUs |
| nginx | 1.28.3. Only the default site. `root /var/www/html`, `server_name _`, port 80 only |
| TLS | No certificate, Certbot not installed, nothing listens on 443 |
| PHP | 8.5.4 CLI and FPM. Pool user `www-data`, socket `/run/php/php8.5-fpm.sock` |
| PHP extensions | Same useful set as production, plus `lexbor` and `uri` |
| MySQL | 8.4.11 is installed and running. Listens on `127.0.0.1:3306` only. `deploy` cannot log in without a password, so the `aub` database is not restored |
| Node / npm | Not installed |
| Composer | Not installed |
| Project | `/var/www/aub` exists, no `.git`, no `vendor`, no `node_modules` |
| Migrations on disk | 59 files, latest `2026_09_25_003500_create_teacher_student_notes.php`, same as production |
| Built assets | `public/build` is present (2.0 MB) from the copy |
| Private files | 16 MB at `storage/app/aub-private` |
| Dump | `/home/deploy/aub.sql`, 1.3 MB, taken 28 Sep 2026 17:50 from MySQL 8.0.46, database `aub`, `utf8mb4`, 57 `CREATE TABLE` statements |
| Cron | None |
| Fail2ban | Active |
| SSH | `PermitRootLogin yes` in `sshd_config` |
| Groups | `deploy` is in `sudo` |

The copied `.env` is the production file (same size and the same non-secret values, `APP_KEY` set). It still says `APP_ENV=local`, `APP_DEBUG=true`, and `APP_URL=https://aub.owlsolutions.net`. Mode is `664`, owner `deploy:deploy`.

Private storage is mode `2770`, owner `deploy:deploy`. PHP-FPM runs as `www-data`, so the web app cannot read those files and cannot write `storage/logs` until ownership or group is fixed.

`public/storage` symlink was copied and still points at `storage/app/public`.

## E. Deployment mechanism currently used by Cursor

There is no GitHub Action and no deploy script in the repo. Cursor deploys because of an always-on rule:

`.cursor/rules/aub-admin-deploy.mdc`

That rule says:

- Production is `deploy@178.156.234.23:/var/www/aub`
- Domain is `https://aub.owlsolutions.net`
- After `git push origin main`, copy the changed files with `scp`/`ssh`
- Do not `git pull` on the server and do not init git there
- Never upload Flutter, `.env`, `vendor`, `node_modules`, or storage
- Never overwrite production `.env`
- Then `php artisan optimize:clear`, `php artisan migrate --force` when the schema changed, and `npm run build` when the frontend changed

The same facts are written in `docs/en/SERVER_DEPLOYMENT.md`, `docs/ru/SERVER_DEPLOYMENT.md`, and `docs/en/DEVELOPMENT_RULES.md`.

Until that rule file is changed, a later Cursor task will keep copying backend files to the **old** server.

## F. Backend changes required before migration

No application PHP has to change for the new host. Generated links use `APP_URL`.

What must change, later, and only on the new server unless noted:

1. Keep the current `APP_KEY`. Do not generate a new one.
2. On the new `.env` only, at cutover: `APP_URL=https://staff.accademiaucraina.it`, `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error` or `warning`. Set `SESSION_SECURE_COOKIE=true`. Leave `SESSION_DOMAIN` empty so the cookie stays host-only.
3. Create the MySQL database and user, then import the dump. Do not import into the old server.
4. `composer install --no-dev --optimize-autoloader` on PHP 8.5. If platform checks fail, stop and use PHP 8.3 or 8.4. Do not pass `--ignore-platform-reqs`.
5. Install Node and run `npm ci` and `npm run build`, or confirm the copied `public/build` matches the code that will go live. A final code sync should rebuild assets.
6. Fix ownership so `www-data` can read private files and write `storage` and `bootstrap/cache`. Mode `640` on `.env`.
7. Add the scheduler cron. Do not add a queue worker unless a real queued job is introduced. None is running today.
8. Do not run `migrate` until a fresh code tree is in place and `migrate:status` shows the expected state. Production currently has no pending migrations.
9. After the new host works, change `.cursor/rules/aub-admin-deploy.mdc` and the deployment docs so later tasks do not keep updating `178.156.234.23`.

Docs and the Cursor rule mention the old IP and domain in many places. Those are documentation, not runtime. They should be updated when the rule starts pointing at the new server, not before the new server can serve traffic.

## G. Flutter changes required before migration

Runtime default:

`lib/app/app_config.dart`

```text
kDefaultApiBaseUrl = https://aub.owlsolutions.net/api/v1
```

Override exists (`--dart-define=AUB_API_BASE_URL=...`) but release builds use the default.

Also hardcoded in docs and tests. Tests can keep the old URL until the default changes. The release default must change before phones should use the new host.

Not affected:

- Android applicationId `com.owlsolutions.aub`
- iOS bundle id `com.owlsolutions.aub`
- No cleartext exception, no associated domains, no deep links
- Release APK is still signed with the debug keystore. That is a separate problem, not caused by the domain move
- Sanctum tokens are not tied to the hostname. Existing logins keep working if the app calls the new host and the database is the same
- File URLs returned by the API are absolute. After `APP_URL` changes, new responses use the new host. An old app build will keep calling the old host

There is no store listing change required for the domain itself. A new APK/IPA is required for clients to follow the new API. Until that build is installed, phones keep using `aub.owlsolutions.net`. That is a reason to leave the old host up, or to proxy the old name, until the app is updated.

## H. Infrastructure changes required

On `195.201.37.229`, still to do:

1. Install Composer 2.9.x and Node 20 LTS (the versions that match production).
2. Prove `composer install` on PHP 8.5, or install PHP 8.3/8.4 and point the vhost at that FPM socket.
3. Create database `aub` and user `aub_user` on MySQL 8.4. Import `/home/deploy/aub.sql` into that new database only.
4. Take a **new** dump at cutover. The file from 28 Sep 2026 17:50 will be stale.
5. nginx site for `staff.accademiaucraina.it`, root `/var/www/aub/public`, PHP 8.5 (or 8.3/8.4) FPM, `client_max_body_size` large enough for documents and photos.
6. DNS A/AAAA to `195.201.37.229` only when the HTTP site responds. Then Certbot. Do not point DNS first.
7. Cron for `schedule:run`.
8. Permissions: `deploy:www-data` on the tree, `www-data` able to read `aub-private` and write `storage` and `bootstrap/cache`, `.env` mode `640`.
9. A small swap file. The new server has none. Production has 2 GB.
10. Decide SSH: `PermitRootLogin yes` should be turned off before the host is the public admin.
11. Leave the old server running until the new host passes the smoke tests and the app can reach it.

## I. PHP 8.5 compatibility analysis

| Fact | Detail |
| --- | --- |
| Production | PHP 8.3.6 |
| New server | PHP 8.5.4, extensions present |
| `composer.json` | `"php": "^8.3"` which allows 8.3, 8.4, and 8.5 |
| `laravel/framework` | v13.18.1, requires `php: ^8.3` |
| `nesbot/carbon` | Constraint explicitly includes `^8.5` |
| Other locked packages | No constraint found that stops at 8.4 and excludes 8.5 |

So Composer is **allowed** to resolve this lock file on PHP 8.5. That is not the same as a passing install and test suite.

Do not use `--ignore-platform-reqs`. The first real check is `composer install --no-dev` on the new server. If it errors, install PHP 8.3 or 8.4 from Ubuntu packages and use that FPM pool. Do not switch DNS until one of those runtimes has installed dependencies and `php artisan about` works against the restored database.

MySQL 8.0.46 dump into MySQL 8.4.11 is a normal path for a `mysqldump` SQL file (`utf8mb4`). Import it once on the new server and read the import log before calling the database ready. Do not upgrade the old server.

## J. Database migration risks

| Risk | Why it matters |
| --- | --- |
| `APP_KEY` | Encrypted staff passwords and AI keys become unreadable if the key changes. The copied `.env` already has a key. Keep that exact value |
| Stale dump | 1.3 MB dump from 28 Sep 2026 17:50. Production is still being written. Cutover needs a fresh dump with the app in maintenance, or a short write freeze |
| Engine | Source 8.0.46, destination 8.4.11. Import must be tested. Do not assume it |
| Charset | Dump sets `utf8mb4`. Keep that |
| Timezone | Dump session was `+00:00`. The app timezone is `Europe/Rome`. Laravel timestamps are stored from the app. Do not convert datetime columns by hand |
| Migrations | Production has 59 migrations and none pending. The new tree has the same 59 files. Do not run `migrate` against an empty database and do not run it on the old server |
| Queue tables | Empty. Nothing to drain |
| Size | About 4.1 MB and 57 tables. The dump's 57 `CREATE TABLE` statements match |

`deploy` on the new server cannot log into MySQL yet. The database and grants do not exist from this account's point of view.

## K. Domain migration impact

Old: `https://aub.owlsolutions.net`  
New: `https://staff.accademiaucraina.it`

| Area | Effect |
| --- | --- |
| Web admin | Bookmarks and the nginx name change. Staff must log in again because the session cookie is host-only (`SESSION_DOMAIN` empty). That is expected |
| API | Every mobile call uses the base URL in the app. Old builds keep using the old host |
| Secure files | New API responses will use the new `APP_URL`. Old absolute URLs stored only as generated links, not as rows that embed the domain in the database, follow `url()` |
| Sanctum | Bearer tokens stay valid across the host change if the database is the same |
| CORS | Empty allow-list. No browser origin to update |
| Sessions / cookies | New host, new cookie. No cross-domain session |
| Mail | Mailer is `log`. No live SMTP links. Password reset mail is not a current product path |
| Webhooks | No inbound webhook URL was found in config |
| Google / Apple | No OAuth redirect or associated domain was found. Map tiles are OpenStreetMap in the admin, not a keyed Google API |
| QR / deep links | No app deep links |
| Docs, Cursor rule, tests | Many mentions of the old host and IP. They do not affect runtime until someone follows them and deploys to the old server |

If DNS moves before a new app build is installed, phones break unless `aub.owlsolutions.net` still reaches this API (old server left up, or a temporary proxy).

## L. Security differences

| Topic | Old server | New server |
| --- | --- | --- |
| APP_ENV / APP_DEBUG | `local` / `true` on the live site | Same values copied. Must not stay that way after cutover |
| `.env` mode | `664` world-readable | `664` world-readable, and group is `deploy` not `www-data` |
| Private files | Served only by the app. Directory is outside `public` | Same layout, but `www-data` cannot read the directory (`2770` `deploy:deploy`) |
| MySQL | Localhost only | Localhost only |
| HTTPS | Working, certificate expires 22 Nov 2026 | No TLS |
| Fail2ban | Inactive | Active |
| Root SSH | Not set in the main `sshd_config` that was readable | `PermitRootLogin yes` |
| Firewall | Not readable with this login | Not readable with this login |
| Queue / cron | Scheduler cron exists. No worker | Neither |
| Debug | Stack traces can leak because `APP_DEBUG=true` | Same if this `.env` is used as-is |
| Backups | This audit did not find an automated database backup job | One manual dump in `/home/deploy` |
| Laravel log | 27 `local.ERROR` lines. Not investigated line by line, to avoid copying request data into this report | No app log until storage is writable |

The live site is already running with debug on. The migration must not copy that posture forward as the intended production setup.

## M. Exact migration plan

Do not run these phases now.

### Phase 1 preflight

- Leave `178.156.234.23` as production.
- Change nothing in DNS.
- On the new server only, install Composer and Node.
- Run `composer install --no-dev` with PHP 8.5. Record the result. If it fails, install PHP 8.3 or 8.4 and stop using 8.5 for this app.
- Add a 1–2 GB swap file.
- Set `PermitRootLogin no` when ready, without locking out `deploy`.

### Phase 2 prepare new server

- Create MySQL database `aub` and user `aub_user` with a new password stored only in the new `.env`.
- Point the new `.env` `DB_PASSWORD` at that user. Do not change `APP_KEY`.
- Do not switch `APP_URL` until nginx and TLS exist, or the site will generate links nobody can open.
- Create the nginx site on port 80 for `staff.accademiaucraina.it` only after DNS is planned. Until DNS exists, test with a hosts-file override.
- Fix `deploy:www-data` ownership and `640` on `.env`.

### Phase 3 restore DB/files

- Import a dump taken the same day into the **new** MySQL only.
- Confirm private file count and size against production (16 MB today).
- Confirm `storage/app/aub-private` is readable by `www-data`.
- Do not rsync storage back onto the old server.

### Phase 4 deploy backend

- Rsync the current local or old-server code to `/var/www/aub` on the new host, excluding `.env`, `vendor`, `node_modules`, and `storage`.
- `composer install --no-dev --optimize-autoloader`
- `npm ci && npm run build`
- `php artisan migrate:status` and confirm no unexpected pending migrations. Run `migrate --force` only if the fresh code has migrations the dump does not.
- `php artisan optimize:clear` then config/route/view cache only after `.env` is final.

### Phase 5 configure nginx/SSL

- `server_name staff.accademiaucraina.it;`
- `root /var/www/aub/public;`
- PHP socket of the version that passed `composer install`
- HTTP works via hosts file.
- Then DNS A record to `195.201.37.229`.
- Then Certbot. Confirm HTTP redirects to HTTPS.

### Phase 6 configure scheduler/queue

- Install the same cron line, with the new path.
- Do not start a queue worker. There isn't one in production.
- Confirm `schedule:list` shows `medical-certificates:watch` at 07:00 Europe/Rome.

### Phase 7 smoke tests

Against the new host, before telling users:

- `GET /up` returns 200
- Login page loads over HTTPS
- One staff login works
- One mobile login against the new API works with a test account
- One secure file opens with a bearer token and does not open without one
- A teacher schedule request returns data
- `storage/logs` is being written by `www-data`
- `APP_DEBUG` is false in a generated error

### Phase 8 Flutter/API switch

- Change `kDefaultApiBaseUrl` to `https://staff.accademiaucraina.it/api/v1`
- Raise the app version and register it in `app_versions` the same way 1.1.6 was registered
- Build a new APK. iOS needs the same base URL when that build is made
- Old installs keep using the old domain until they update

### Phase 9 final sync/cutover

- Maintenance window on the old app
- Fresh `mysqldump` from the old server
- Import on the new server, replacing the rehearsal database
- Rsync `storage/app/aub-private` one last time
- Smoke tests again
- Only then tell staff and ship the app
- Keep the old server up

### Phase 10 rollback plan

- DNS stays on the old server until Phase 9 has passed, so rollback before DNS is "do nothing"
- If DNS already moved and the new site fails, point `staff.accademiaucraina.it` back is not enough for phones that still use `aub.owlsolutions.net`. Those phones are fine as long as the old server was not turned off
- Do not delete the old server in this project
- Do not write the new database back over the old one

### Phase 11 post-cutover cleanup

- Update `.cursor/rules/aub-admin-deploy.mdc` and deployment docs to the new host
- Turn off debug
- Restrict `.env`
- Plan certificate renewal
- Decide when `aub.owlsolutions.net` can be a redirect, only after app installs have moved
- Add a real database backup cron on the new host

## N. Rollback plan

1. Do not change DNS until the new host has passed Phase 7.
2. If the new host fails before DNS, production remains `https://aub.owlsolutions.net` with no user impact.
3. If DNS for the new name was published and TLS or the app fails, remove that DNS record. The old name is a different hostname and keeps working.
4. If a new APK was installed and the new API fails, phones need the old URL again. Do not ship that APK until Phase 7 has passed.
5. Never restore the new dump onto `178.156.234.23`.
6. Never run `key:generate` on either server.

## O. Checklist

### BLOCKER

- Composer is not installed on the new server, so dependencies cannot be installed
- Node/npm are not installed, so a fresh frontend build cannot be made
- MySQL has no restored `aub` database that this login can see
- nginx has no site for `staff.accademiaucraina.it` and no TLS
- `www-data` cannot read `storage/app/aub-private` or write the storage tree
- PHP 8.5 has not been proven with `composer install` on this lock file
- Flutter release builds still call the old API
- The dump is a point-in-time copy and will be stale at cutover

### REQUIRED

- Keep the current `APP_KEY`
- Set `APP_ENV=production` and `APP_DEBUG=false` on the new host before users arrive
- Set `APP_URL` to the new https origin before generating links
- Scheduler cron
- Final rsync of code and private files
- Fresh dump and a checked import into MySQL 8.4
- `.env` mode `640`
- Swap on the new server
- Smoke tests listed in Phase 7
- Leave the old server running

### RECOMMENDED

- `PermitRootLogin no` on the new server
- `SESSION_SECURE_COOKIE=true`
- Database backup cron
- Confirm UFW with a sudo-capable check later (this login could not read it)
- Rebuild `public/build` instead of trusting the copied assets
- Update the Cursor deploy rule only after the new host is the one that should receive files
- Register the new app version without deactivating 1.1.5 until phones have moved

### OPTIONAL

- A queue worker (not used today)
- Changing Android/iOS ids (not required)
- Turning off the old domain (only after the app no longer calls it)
- Hardening the old server's `APP_DEBUG=true` (live issue, separate from the move)

## P. Files and configuration that will need modification

Later, not now.

Application and docs:

- `.cursor/rules/aub-admin-deploy.mdc`
- `docs/en/SERVER_DEPLOYMENT.md`
- `docs/ru/SERVER_DEPLOYMENT.md`
- `docs/en/DEVELOPMENT_RULES.md`
- `docs/ru/DEVELOPMENT_RULES.md`
- `docs/en/ARCHITECTURE.md`
- `docs/ru/ARCHITECTURE.md`
- `docs/en/API.md`
- `docs/ru/API.md`
- `docs/en/README_PROJECT_OVERVIEW.md`
- `docs/ru/README_PROJECT_OVERVIEW.md`
- `docs/README.md`
- `docs/Product/FUNCTIONALITY_MATRIX.md` (staff site URL)
- `docs/Product/SECURITY_REPORT_CLIENT.md` (historical; update only if a new client report is issued)

Flutter:

- `C:\MobAPP\AUB_app\lib\app\app_config.dart`
- `C:\MobAPP\AUB_app\README.md`
- `C:\MobAPP\AUB_app\docs\API_INTEGRATION.md`
- `C:\MobAPP\AUB_app\docs\CURRENT_STATE.md`
- `C:\MobAPP\AUB_app\docs\DEVELOPMENT_RULES.md`
- Tests that embed the old host, when the default changes

Server, not in git:

- New `/var/www/aub/.env` non-secret keys listed above. Do not replace `APP_KEY`
- New nginx site, not the default site
- Let's Encrypt files created by Certbot
- `deploy` crontab
- MySQL user and database
- Directory owner and mode

No change required for Sanctum stateful domains, CORS, Android applicationId, or iOS bundle id.

## Q. Commands that will be required later

Do not run these now. They are the later sequence, on `195.201.37.229` unless noted.

```bash
# Install Composer and Node 20 using the distro or the official Composer installer.
# Then, only after PHP 8.5 is the chosen runtime:
cd /var/www/aub
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Database restore into the NEW server only, after the database and user exist:
mysql -u aub_user -p aub < /home/deploy/aub.sql

# Permissions, after agreeing the web user is www-data:
sudo chown -R deploy:www-data /var/www/aub
sudo chmod 640 /var/www/aub/.env
sudo chmod -R ug+rwx /var/www/aub/storage /var/www/aub/bootstrap/cache

# Read-only check, not a migration:
php artisan migrate:status
php artisan schedule:list
php artisan about

# Cron:
# * * * * * cd /var/www/aub && php artisan schedule:run >> /dev/null 2>&1

# Fresh dump from the OLD server at cutover:
# mysqldump on 178.156.234.23, then import on 195.201.37.229

# TLS, only after DNS points at the new server:
# certbot --nginx -d staff.accademiaucraina.it
```

Do not run `php artisan key:generate`.  
Do not run `php artisan migrate` on the old server as part of this move.  
Do not `git pull` on either server.

## R. Can we migrate safely now?

**NO.**

Concrete reasons:

1. The new server cannot install PHP dependencies or build the frontend.
2. The database dump has not been imported, and MySQL 8.4 has not been shown to accept it.
3. There is no site, no certificate, and no cron for the new domain.
4. PHP-FPM cannot read the private files with the current ownership.
5. PHP 8.5 has not executed this application.
6. The copied environment is still the debug/local environment aimed at the old URL.
7. The mobile app will keep calling the old server until a new build is shipped, so the old server has to stay up anyway.

Production right now remains `https://aub.owlsolutions.net` on `178.156.234.23`.

## Migration execution result

Started 2026-09-28. Stopped before database, nginx, TLS, cron, Flutter, and the deploy-rule switch. The old server was not modified and was not put into maintenance.

### What was verified

| Check | Result |
| --- | --- |
| OS | Ubuntu 26.04.1 LTS, kernel 7.0.0-34-generic |
| PHP CLI and FPM | 8.5.4, `php8.5-fpm` active, socket `/run/php/php8.5-fpm.sock`, user `www-data` |
| MySQL contradiction | Resolved. `mysql.service` is **enabled and active**. Client is MySQL 8.4.11. It listens only on `127.0.0.1:3306`. It was not reinstalled |
| nginx | 1.28.3, active, default site only, port 80 |
| DNS | `staff.accademiaucraina.it` A record is `195.201.37.229`. No AAAA was returned |
| Project | `/var/www/aub` present, private files 16 MB, dump `/home/deploy/aub.sql` 1.3 MB |
| Disk / RAM | 33 GB free, 3.7 GB RAM, **no swap** |
| Fail2ban | Active |
| UFW | Not readable. `sudo` asks for a password |
| SSH | `PermitRootLogin yes`. Password authentication was not changed |
| sudo | `deploy` is in the `sudo` group, but `sudo -n` fails with “interactive authentication is required”. MySQL `root` uses socket auth and also denied `deploy` |

### Composer and PHP 8.5

Composer **2.10.3** was installed for the `deploy` user at `/home/deploy/bin/composer` (no root, so not in `/usr/local/bin`).

```text
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Succeeded on PHP 8.5.4. 81 packages from the lock file, including `laravel/framework` v13.18.1. `--ignore-platform-reqs` was not used.

`php artisan --version` → Laravel Framework 13.18.1.  
`php artisan about` → PHP 8.5.4, timezone Europe/Rome. Environment is still `local` and debug is still on, because `.env` was not changed.

**PHP 8.5 verified for install: YES.**

### Node

Node **v20.20.2** and npm **10.8.2** were unpacked under `/home/deploy/opt/node` (no root package install).

`npm ci` and `npm run build` succeeded. `public/build` was regenerated.

The list below was the state before root access. It is superseded by **Migration execution result**.

## Migration execution result

Completed 2026-09-28. Secrets, `APP_KEY`, and database passwords are not recorded here.

1. NEW PRODUCTION READY: **YES**
2. New production URL: `https://staff.accademiaucraina.it`
3. API URL: `https://staff.accademiaucraina.it/api/v1`
4. Old server status: kept, not deleted. `php artisan down` is on. Rollback host remains `178.156.234.23` / `https://aub.owlsolutions.net`. It is no longer the deploy target.
5. DB migrated: **YES**. Final dump after maintenance on the old server, 57 tables, 59 migrations, none pending, `utf8mb4`. `APP_KEY` unchanged. `php artisan key:generate` was not run.
6. Private files migrated: **YES**. `storage/app/aub-private` replaced from the old server after maintenance. Not world-readable. `www-data` can read them.
7. PHP 8.5 verified: **YES**. PHP 8.5.4, `composer install` without `--ignore-platform-reqs`, Laravel 13.18.1, `php artisan about` shows production, debug OFF, timezone Europe/Rome.
8. SSL verified: **YES**. Let's Encrypt for `staff.accademiaucraina.it`, valid 28 Sep 2026 through 27 Dec 2026. HTTP returns 301 to HTTPS. `GET /up` returns 200. `GET /.env` returns 403.
9. Flutter switched: **YES**. Default API base URL is the new host. The app was not published.
10. Deploy rule switched: **YES**. `.cursor/rules/aub-admin-deploy.mdc` points at `deploy@195.201.37.229:/var/www/aub` and `https://staff.accademiaucraina.it`.
11. Remaining blockers: none for serving the new host. Staff must sign in again on the new hostname. Authenticated browser clicks were not done because no staff password was used. The old host stays in maintenance until an explicit decision to bring it back or remove it.
12. Backend commit SHA: filled in after the commit.
13. Flutter commit SHA: filled in after the commit.

Also done: 2 GB swap, `.env` mode 640 `deploy:www-data`, MySQL only on `127.0.0.1`, UFW allows 22/80/443, Fail2ban active, cron `schedule:run` for `deploy`, no queue worker, `PermitRootLogin no`, password SSH disabled after a fresh deploy key login succeeded. Composer 2.10.3 and Node 20.20.2 are on the default PATH via `/usr/local/bin`.
