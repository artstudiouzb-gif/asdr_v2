# Деплой и чек-лист первого релиза

ArtStudio CMS — чистый PHP 8.2+ / MySQL(MariaDB), без Composer и npm на
production. Эти инструменты используются только для проверок разработки и CI.
Ниже — минимальные шаги для боевого запуска.

## 1. Требования окружения

- **PHP 8.2+** с расширениями: `pdo_mysql`, `zip`, `gd`, `curl`, `mbstring`,
  `dom`, `openssl`.
- **MySQL 5.7+/MariaDB 10.3+**.
- Веб-сервер Apache (с `mod_rewrite`, `mod_headers`) или nginx.

## 2. Document root

- **Рекомендуется:** указать document root на каталог `public/`.
- **Fallback** (дешёвый shared-хостинг без смены docroot): корневой
  `.htaccess` уже переписывает трафик в `public/` и запрещает прямой доступ к
  `.php`. Требуется `AllowOverride All` (иначе `.htaccess` игнорируется).

Для nginx — см. секцию «nginx» ниже и `docs/nginx.conf.example`.

## 3. Установка

1. Загрузите файлы на сервер (`config/config.php` НЕ копируйте — его создаст
   установщик; убедитесь, что каталоги `storage/` и `public/uploads/` доступны
   на запись).
2. Откройте сайт в браузере — произойдёт редирект на `/install`.
3. Пройдите 4 шага: проверка окружения → БД → сайт → супер-администратор.
   Установщик подключится к базе (или создаст её, если есть права), импортирует
   `database/schema.sql`, запишет `config/config.php` и `storage/installed.lock`.
4. Первый вход в `/admin` форсит настройку 2FA (TOTP) — обязательно.
5. Создайте главную страницу (галка «Сделать главной страницей сайта»).

### Если шаг «База данных» выдаёт ошибку 1044 (Access denied … to database)

На shared-хостинге у пользователя MySQL нет глобальных прав — базу и
пользователя нужно создать в панели хостинга **и назначить пользователя на
базу со всеми привилегиями** (в cPanel это отдельный третий шаг: «Базы данных
MySQL» → «Добавить пользователя в базу данных» → отметить ALL PRIVILEGES).
После назначения повторите шаг установщика. Хост подключения на
shared-хостинге обычно `localhost`. Установщик подключается к уже созданной
базе напрямую и создаёт базу сам только там, где у пользователя есть право
`CREATE` (VPS/свой сервер).

## 4. HTTPS

Разверните за HTTPS — тогда автоматически включатся HSTS и `Secure`-cookies
(проверка `SecurityHeaders::isHttps()`).

## 5. Обязательная ручная проверка безопасности на проде

Встроенный PHP-сервер не читает `.htaccess`, поэтому это проверяется только на
боевом Apache/nginx. Все URL ниже должны отдавать **403 или 404**:

- `https://домен/config/config.php`
- `https://домен/database/create_admin.php`
- `https://домен/database/schema.sql`
- `https://домен/storage/logs/`

Если хоть один отдаёт содержимое — включите `AllowOverride All` (Apache) или
примените nginx-конфиг из README. Подробности — в `SECURITY.md`.

## 6. Cron (фоновые воркеры)

Без cron не работают очереди писем/вебхуков/соцсетей, бэкапы и heartbeat.
Пример crontab:

```
* * * * *  php /path/to/app/Console/mail_worker.php     >> /path/to/storage/logs/mail_worker.log 2>&1
* * * * *  php /path/to/app/Console/webhook_worker.php  >> /path/to/storage/logs/webhook_worker.log 2>&1
*/5 * * * * php /path/to/app/Console/social_worker.php   >> /path/to/storage/logs/social_worker.log 2>&1
*/5 * * * * php /path/to/app/Console/push_worker.php     >> /path/to/storage/logs/push_worker.log 2>&1
0 3 * * *  php /path/to/app/Console/backup_worker.php    >> /path/to/storage/logs/backup_worker.log 2>&1
30 3 * * * php /path/to/app/Console/gdpr_cleanup.php     >> /path/to/storage/logs/gdpr_cleanup.log 2>&1
0 9 * * 1  php /path/to/app/Console/digest_worker.php    >> /path/to/storage/logs/digest_worker.log 2>&1
```

`/health` возвращает `degraded` и шлёт алерт, если воркер перестал запускаться.

### Автобэкапы и ротация

`backup_worker.php` (строка `0 3 * * *` выше) каждую ночь снимает полный бэкап
(дамп БД + загрузки, `.zip` + `.sha256`) и ротирует старые копии по схеме
«**7 дневных + 4 недельных**»: все копии за последние 7 дней, дальше — по одной
самой свежей на неделю за 4 недели, остальное удаляется. Лимиты меняются через
`BACKUP_KEEP_DAILY` / `BACKUP_KEEP_WEEKLY` (0 в `BACKUP_KEEP_DAILY` возвращает
простую ротацию по `BACKUP_RETENTION_DAYS`). При сбое бэкапа уведомление
приходит в Telegram: и алерт из логов (`TELEGRAM_*` в config), и сообщение от
бота на chat_id из настройки «Уведомления о заявках форм».

## 7. Быстродействие (OPcache, сжатие, HTTP/2)

Оптимизации в коде (кэш страниц, WebP, lazy-load, версионированная статика)
включаются в админке, раздел «Производительность». Три пункта ниже — уровень
сервера, настраиваются один раз при развёртывании и дают основной прирост.

### OPcache — обязательно на проде

Скомпилированный PHP-байткод держится в памяти вместо перекомпиляции на каждый
запрос (обычно ×2–3 к скорости ответа). В `php.ini` (или pool-конфиге FPM):

```ini
opcache.enable=1
opcache.memory_consumption=192
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=60   ; правки файлов подхватываются в течение минуты
```

После обновления кода через FTP кэш обновится сам (не позднее
`revalidate_freq` секунд); при деплое через перезапуск FPM — мгновенно.
Проверка: `php -i | grep opcache.enable` или блок Zend OPcache в `phpinfo()`.

### Сжатие ответов (gzip/brotli)

HTML/CSS/JS/JSON ужимаются в 3–5 раз. Для nginx строки `gzip …` уже включены в
`docs/nginx.conf.example`. Для Apache (mod_deflate) добавьте в конфиг хоста или
корневой `.htaccess` хостинга:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/xml \
        application/json application/javascript image/svg+xml
</IfModule>
```

Проверка: `curl -sI -H 'Accept-Encoding: gzip' https://домен/ | grep -i content-encoding`
должен показать `gzip` (или `br`).

### HTTP/2

Включается на стороне веб-сервера вместе с HTTPS и ускоряет параллельную
загрузку статики. nginx: `listen 443 ssl http2;` в серверном блоке. Apache:
`a2enmod http2` и `Protocols h2 http/1.1` в виртуальном хосте. На большинстве
панельных хостингов включён по умолчанию — проверьте:
`curl -sI https://домен/ | head -1` (ответ `HTTP/2 200`).

## 8. Рекомендуется к первому релизу (опционально)

- **SMTP** (`SMTP_*` в окружении/config) — сброс пароля и уведомления.
- **Telegram** (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`) — алерты об ошибках.
- **`BACKUP_EXTERNAL_DIR`** — копия бэкапа вне сервера (иначе локальная копия
  бесполезна при полном отказе). Один раз прогоните восстановление:
  `php database/restore.php <архив> <тестовая_БД> <каталог>`.
- Настройки: аналитика (по ID), cookie-consent, favicon/PWA, срок хранения ПДн.

## 9. Обновление существующей установки

```
php database/migrate.php status   # какие миграции новые
php database/migrate.php           # накатить новые миграции
```

Для выпуска после загрузки новой версии используйте fail-fast сценарий:

```bash
php scripts/release.php https://asr.artstudio.uz
```

Он создаёт резервную копию до миграций, применяет их, очищает кеш и запускает
проверки сервера и публичного сайта. Сам код скрипт не скачивает: `git pull`,
распаковка архива или загрузка через панель хостинга выполняются до него.

Новые установки получают полную схему из `schema.sql`; существующие — через
миграции в `database/migrations/`.

## Если сайт отвечает «403 Forbidden»

Типичные причины на shared-хостинге:

0. **Устаревшие `.htaccess` (до июля 2026) на LiteSpeed-хостинге** — старая
   схема «запретить все `.php` + точечно разрешить index.php» на LiteSpeed и
   части shared-хостингов отдавала 403 всему сайту, включая установщик
   (сообщение «Additionally, a 403 Forbidden error was encountered while
   trying to use an ErrorDocument…»). Решение: залейте актуальные корневой
   `.htaccess` и `public/.htaccess` из репозитория — защита переписана на
   rewrite-правила, которые работают одинаково на Apache и LiteSpeed
   (проверено на обоих вариантах document root).

1. **Document root указывает на корень проекта, а mod_rewrite выключен** —
   правила из `.htaccess` не работают, в корне нет index-файла → 403.
   Решение: в панели хостинга укажите document root на папку `public/`
   (предпочтительно) либо попросите включить `mod_rewrite` и
   `AllowOverride All` для каталога сайта.
2. **`AllowOverride` не разрешает директивы** (`Options`, `FileInfo`) —
   Apache игнорирует `.htaccess` целиком или падает на `Options -Indexes`.
   Решение: `AllowOverride All` в конфиге виртуального хоста.
3. **Права на файлы**: каталоги 755, файлы 644, владелец — пользователь
   веб-сервера/FTP.

Проверка: откройте `/index.php` напрямую — если он открывается, а `/` нет,
дело в DirectoryIndex/rewrite; если 403 и там — в правах или AllowOverride.

## nginx

nginx не читает `.htaccess`. Используйте готовый серверный блок из
`docs/nginx.conf.example`: root указывает на `public/`, исполняются только
`index.php` и `download.php`, служебные файлы и прочие `.php` закрыты,
версионированные ассеты кэшируются на год. После правки:
`nginx -t && systemctl reload nginx`.

Приложение не зависит от веб-сервера: на Apache работает через `.htaccess`
(в комплекте), на nginx — через этот конфиг.
