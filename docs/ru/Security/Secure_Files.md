# AUB — Безопасные файлы

Инвариант всего продукта: **все пользовательские, персональные и внутренние файлы по умолчанию private.**

```
Пользовательский/внутренний файл → SecureFileService → диск aub_private → авторизация → контролируемый ответ
```

Запрещено:

```
UploadedFile → public disk → прямой URL /storage
```

Laravel участвует в каждом доступе к private-файлу. Новый функционал с файлом обязан зарегистрировать category в `config/aub-files.php` и использовать `SecureFileService`. Нельзя заводить отдельный storage для Students, Teachers или будущего модуля.

Public disk допустим только для действительно публичных assets (логотип, CSS/JS, маркетинг). Они не используют `SecureFile`.

## Диск

| Диск | Корень | HTTP |
|------|--------|------|
| `aub_private` | `storage/app/aub-private` (override `AUB_PRIVATE_DISK_ROOT`) | Нет. Нет symlink, нет nginx alias |
| `aub_legacy_quarantine` | `storage/app/aub-legacy-quarantine` | Нет. Phase A для старых public-объектов |

PHP-FPM (`www-data`) должен читать объекты. Каталоги `2770` `deploy:www-data`, файлы `0640`. Дефолты Flysystem private (`0700`/`0600`) переопределены в `config/filesystems.php`. PHPUnit пишет в `/tmp/aub-phpunit-*`, не в production storage.

Опциональные env-ключи имеют безопасные default в config. Секреты в `.env` для этого не нужны.

Физический путь непрозрачный: `objects/{aa}/{uuid}`. Оригинальное имя только в метаданных БД.

## Сущность

Таблица `secure_files` (soft delete). Полиморфный `attachable` (`student`, `teacher`, `parent`, `user`). Варианты: `original`, `thumbnail` (`parent_id`). Источник правды для фото/документов — эта таблица, не `student_photo_path` / `photo_path`.

Хелперы: `Student::secureFiles()`, `profilePhoto()`, `secureFile($category)`.

API никогда не сериализует `disk`, `path`, `stored_name`, `sha256`, `uploaded_by`.

## Категории

Неизвестная category: **DENY**. MIME определяется на сервере (`finfo` по байтам). Client `Content-Type` и расширение имени не доверяются. Изображения декодируются и перекодируются (EXIF/GPS снимаются). SVG, HTML, PHP, JS, EXE, ZIP не разрешены.

| category | attachable | кто может смотреть | mime | max | inline | audit_view |
|----------|----------|---------------------|------|-----|--------|------------|
| `profile_photo` | student, teacher, parent, user | admin; staff web; студент сам; родитель своего ребёнка; преподаватель свой + ученики своего контекста | jpeg, png, webp | 5 MB | да | нет |
| `identity_document` | student, parent | admin; staff с `customers.*` | pdf, jpeg, png | 10 MB | нет | да |
| `medical_document` | student | admin; staff с `customers.*` | pdf, jpeg, png | 10 MB | нет | да |
| `consent_document` | student | admin; staff с `customers.*` | pdf | 10 MB | нет | да |
| `consent_general_regulation` | student | как consent | pdf | 10 MB | нет | да |
| `consent_minor_entry_exit` | student | как consent | pdf | 10 MB | нет | да |
| `consent_rights_release` | student | admin; staff с `customers.*` | pdf | 10 MB | нет | да |
| `certificate` | student, teacher | admin; staff соответствующей CRM-страницы | pdf | 10 MB | нет | да |
| `general_document` | student, teacher, parent | admin; staff соответствующей CRM-страницы | pdf | 10 MB | нет | да |
| `teacher_document` | teacher | admin; staff с `teachers.*` | pdf | 10 MB | нет | да |
| `report_card` | student | admin; staff с `customers.*` | pdf | 10 MB | нет | да |
| `message_attachment` | student, teacher, parent, user | admin; staff соответствующей CRM-страницы | pdf, jpeg, png, webp | 10 MB | нет | да |

Мобильные акторы не получают identity / medical / consent / general, пока отдельная задача не добавит явное правило. Для преподавателя эти категории по умолчанию **DENY**.

## HTTP

| Маршрут | Auth | Примечание |
|---------|------|------------|
| `GET /secure-files/{uuid}` | web session + role | ACL через `FileAccessService`. Чужой известный UUID → **404** |
| `GET /secure-files/{uuid}/download` | web session + role | Attachment |
| `GET /api/v1/files/{uuid}` | Sanctum + `mobile.actor` | Бинарь. Без auth → 401. Без права → 404 |

Нет signed public URL. Нет bearer token в query string.

Заголовки: `X-Content-Type-Options: nosniff`. `Cache-Control: private` (картинки) или `private, no-store` (sensitive). Streamed responses.

## Аудит

События `ActivityLogger`: `secure_file.uploaded`, `secure_file.replaced`, `secure_file.deleted`, `secure_file.downloaded`, `secure_file.viewed` (только если `audit_view`). GET thumbnail / profile-photo не пишется.

## Миграция

```
php artisan aub:secure-files:migrate --dry-run
php artisan aub:secure-files:migrate
```

Phase A: copy → private → проверка SHA-256 → строка `secure_files` → обнуление legacy path → **перенос** public-объекта в quarantine. Phase B (`--purge-legacy`) позже по желанию. Команда идемпотентна.

## Architecture guard

`PublicStorageArchitectureGuard` + PHPUnit. В `app/`, `resources/js/`, `routes/`, `database/seeders/` нельзя `disk('public')`, `storePublicly`, `Storage::url`, `/storage/`, кроме whitelist команды миграции.

## Flutter

`AuthenticatedImage` / `AubAvatar` грузят `/api/v1/files/{uuid}` через существующий Dio-клиент (заголовок Bearer). Только memory cache. 401 идёт в глобальный auth. Нет фото / 404 → инициалы.
