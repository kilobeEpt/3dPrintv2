# 🚀 3D Print Pro - Полное руководство по развертыванию

## Обзор

Это руководство проведет вас через все шаги развертывания бекенда 3D Print Pro на хостинге.

## 📋 Требования

### Минимальные требования сервера:
- **PHP**: 7.4 или выше (рекомендуется 8.0+)
- **MySQL**: 5.7 или выше (рекомендуется 8.0+)
- **Веб-сервер**: Apache 2.4+ или Nginx 1.18+
- **Composer**: 2.x
- **Память PHP**: минимум 128MB (рекомендуется 256MB)
- **Разрешения**: возможность записи в storage/

### Необходимые PHP расширения:
- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `openssl`
- `curl` (для Telegram интеграции)

## 🔧 Пошаговое развертывание

### Шаг 1: Загрузка файлов

1. Загрузите всю папку `backend/` на ваш хостинг
2. Типичная структура:
   ```
   /home/your-account/
   ├── public_html/          # Корень веб-сайта
   │   ├── index.html        # Главная страница сайта
   │   ├── admin.html
   │   └── ...
   └── backend/              # Бекенд API (может быть вне public_html)
       ├── public/           # Публичная папка API
       ├── src/
       ├── vendor/           # Composer зависимости
       └── ...
   ```

### Шаг 2: Установка Composer зависимостей

Подключитесь к серверу через SSH и выполните:

```bash
cd /path/to/backend
composer install --no-dev --optimize-autoloader
```

**Проблемы с Composer версий:**

Если на хостинге Composer 1.x, а нужен 2.x:

```bash
# Обновление Composer до версии 2
composer self-update --2

# Или использование локальной версии
php composer.phar install --no-dev --optimize-autoloader
```

**Если Composer не установлен:**

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

**Альтернатива без SSH:**

Запустите `composer install` локально и загрузите папку `vendor/` на сервер через FTP.

### Шаг 3: Создание базы данных

1. Войдите в панель управления хостингом (cPanel, ISPmanager и т.д.)
2. Откройте **MySQL Databases** или **phpMyAdmin**
3. Создайте новую базу данных:
   - Имя: `ch167436_3dprint` (или свое)
   - Кодировка: `utf8mb4_unicode_ci`
4. Создайте пользователя БД и назначьте ему все права на созданную БД
5. Запомните: `host`, `database name`, `username`, `password`

### Шаг 4: Импорт схемы базы данных

1. Откройте **phpMyAdmin**
2. Выберите созданную базу данных
3. Перейдите на вкладку **Import** (Импорт)
4. Загрузите файлы по порядку:
   - `backend/database/migrations/20231113_initial.sql` (схема)
   - `backend/database/seeds/initial_data.sql` (начальные данные)
5. Нажмите **Go** (Выполнить)

### Шаг 5: Создание администратора

#### Вариант А: Через SSH

```bash
cd /path/to/backend
php database/seeds/seed-admin-user.php
```

#### Вариант Б: Через phpMyAdmin

Выполните SQL запрос:

```sql
-- Замените значения на свои!
INSERT INTO users (login, password_hash, name, email, role, active, created_at, updated_at)
VALUES (
    'admin',                                          -- Логин
    '$2y$10$abcdefghijklmnopqrstuvwxyz123456789',   -- Хеш пароля (см. ниже)
    'Администратор',                                  -- Имя
    'admin@3dprintpro.ru',                           -- Email
    'admin',                                          -- Роль
    TRUE,                                             -- Активен
    NOW(),                                            -- Дата создания
    NOW()                                             -- Дата обновления
);
```

**Генерация хеша пароля:**

```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

Или используйте скрипт `backend/bin/reset-password.php`.

### Шаг 6: Конфигурация .env файла

1. Скопируйте файл примера:
   ```bash
   cp .env.example .env
   ```

2. Отредактируйте `.env`:

```env
# Окружение
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# CORS - укажите адрес вашего фронтенда
CORS_ORIGIN=https://yourdomain.com

# База данных
DB_HOST=localhost                 # Обычно localhost
DB_PORT=3306                      # Стандартный порт MySQL
DB_DATABASE=ch167436_3dprint     # Имя вашей БД
DB_USERNAME=your_db_user         # Пользователь БД
DB_PASSWORD=your_db_password     # Пароль БД
DB_CHARSET=utf8mb4

# JWT - ОБЯЗАТЕЛЬНО измените на случайную строку!
JWT_SECRET=ваш_случайный_секретный_ключ_64_символа_или_больше_используйте_генератор
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600

# Telegram (опционально)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Администратор по умолчанию (для скриптов)
ADMIN_LOGIN=admin
ADMIN_PASSWORD=your_secure_password
ADMIN_NAME=Администратор
ADMIN_EMAIL=admin@yourdomain.com
```

**ВАЖНО: Генерация JWT_SECRET**

```bash
# Способ 1: OpenSSL
openssl rand -base64 64

# Способ 2: PHP
php -r "echo bin2hex(random_bytes(32));"

# Способ 3: Online генератор
# https://randomkeygen.com/
```

### Шаг 7: Настройка веб-сервера

#### Apache (с mod_rewrite)

Файл `.htaccess` уже создан в `public/.htaccess`. Проверьте, что:

1. `mod_rewrite` включен
2. `AllowOverride All` установлен для директории

Если API доступен через поддомен или подпапку:

**Поддомен (api.yourdomain.com):**
```apache
<VirtualHost *:80>
    ServerName api.yourdomain.com
    DocumentRoot /path/to/backend/public
    
    <Directory /path/to/backend/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/api-error.log
    CustomLog ${APACHE_LOG_DIR}/api-access.log combined
</VirtualHost>
```

**Подпапка (yourdomain.com/backend):**

В корневом `.htaccess` добавьте:
```apache
RewriteEngine On
RewriteRule ^backend/(.*)$ backend/public/$1 [L]
```

#### Nginx

Создайте конфигурацию (или используйте `nginx.conf.example`):

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /path/to/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /composer\.(json|lock)$ {
        deny all;
    }
}
```

Перезагрузите Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Шаг 8: Установка прав доступа

```bash
cd /path/to/backend

# Права на папки storage
chmod -R 755 storage/
chmod -R 775 storage/logs/
chmod -R 775 storage/cache/

# Если веб-сервер запущен под другим пользователем
chown -R www-data:www-data storage/

# Или (cPanel)
chown -R your-username:your-username storage/
```

### Шаг 9: Тестирование установки

#### 1. Тест конфигурации сервера

Откройте в браузере:
```
https://yourdomain.com/backend/test-setup.php
```

Или через CLI:
```bash
php test-setup.php
```

Все проверки должны пройти (зеленые галочки).

#### 2. Тест подключения к БД

Откройте в браузере:
```
https://yourdomain.com/backend/test-db.php
```

Или через CLI:
```bash
php test-db.php
```

Должны быть найдены все таблицы и админ пользователь.

#### 3. Тест API роутов

Откройте в браузере:
```
https://yourdomain.com/backend/test-routes.php
```

Все endpoints должны отвечать корректно.

#### 4. Проверка health endpoint

```bash
curl https://yourdomain.com/backend/public/api/health
```

Ожидаемый ответ:
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01 12:00:00",
  "environment": "production",
  "database": {
    "connected": true,
    "message": "Database connection successful",
    "version": "8.0.32",
    "database": "ch167436_3dprint"
  }
}
```

### Шаг 10: Настройка фронтенда

Обновите файлы `index.html` и `admin.html`:

```html
<!-- В секции <head> -->
<meta name="api-base-url" content="https://yourdomain.com/backend/public">
```

Варианты:

**Поддомен:**
```html
<meta name="api-base-url" content="https://api.yourdomain.com">
```

**Тот же домен, подпапка:**
```html
<meta name="api-base-url" content="/backend/public">
```

**Локальная разработка:**
```html
<meta name="api-base-url" content="http://localhost:8080">
```

## 🔒 Безопасность

### Обязательные меры:

1. **Измените JWT_SECRET** в `.env` на случайную строку
2. **Измените пароль администратора** после первого входа
3. **Установите APP_DEBUG=false** в production
4. **Настройте HTTPS** (Let's Encrypt)
5. **Ограничьте CORS_ORIGIN** только вашим доменом
6. **Убедитесь, что .env недоступен** из браузера

### Проверка безопасности:

```bash
# .env не должен быть доступен
curl https://yourdomain.com/backend/.env
# Ожидаемо: 403 Forbidden

# composer.json не должен быть доступен
curl https://yourdomain.com/backend/composer.json
# Ожидаемо: 403 Forbidden
```

## 🐛 Устранение неполадок

### Проблема: "Not Found" на всех роутах

**Причина:** URL rewriting не работает

**Решение:**
- Apache: включите `mod_rewrite`
- Проверьте `.htaccess` в `public/`
- Убедитесь в `AllowOverride All`
- Nginx: проверьте `try_files` в конфигурации

### Проблема: "vendor/autoload.php not found"

**Причина:** Composer зависимости не установлены

**Решение:**
```bash
cd backend
composer install --no-dev --optimize-autoloader
```

### Проблема: "Database connection failed"

**Причина:** Неверные данные БД в `.env`

**Решение:**
1. Проверьте `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
2. Убедитесь, что БД создана
3. Проверьте права пользователя БД
4. Запустите `php test-db.php`

### Проблема: "Class not found"

**Причина:** Autoloader не обновлен

**Решение:**
```bash
composer dump-autoload --optimize
```

### Проблема: "Permission denied" в storage/

**Причина:** Неверные права доступа

**Решение:**
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### Проблема: CORS ошибки

**Причина:** CORS_ORIGIN не настроен

**Решение:**
Проверьте `.env`:
```env
CORS_ORIGIN=https://yourdomain.com
```

Для нескольких доменов:
```env
CORS_ORIGIN=https://yourdomain.com,https://www.yourdomain.com
```

### Проблема: JWT "Invalid token"

**Причина:** JWT_SECRET не совпадает или истек срок токена

**Решение:**
1. Проверьте JWT_SECRET в `.env`
2. Выполните повторный логин
3. Проверьте синхронизацию времени сервера

### Проблема: 500 Internal Server Error

**Причина:** Ошибки PHP или конфигурации

**Решение:**
1. Включите отображение ошибок временно:
   ```env
   APP_DEBUG=true
   ```
2. Проверьте логи сервера:
   ```bash
   tail -f /var/log/apache2/error.log
   # или
   tail -f /var/log/nginx/error.log
   ```
3. Проверьте логи приложения:
   ```bash
   tail -f storage/logs/app.log
   ```

## 📊 Мониторинг

### Проверка работоспособности

Настройте регулярные проверки health endpoint:

```bash
# Cron задача (каждые 5 минут)
*/5 * * * * curl -f https://yourdomain.com/backend/public/api/health || echo "API is down!"
```

### Логирование

Логи приложения хранятся в:
```
backend/storage/logs/app.log
```

Ротация логов (в crontab):
```bash
0 0 * * * find /path/to/backend/storage/logs -name "*.log" -mtime +30 -delete
```

## 🚀 Оптимизация производительности

### OPcache (PHP)

В `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### База данных

```sql
-- Добавьте индексы для быстрых запросов
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_services_active ON services(active, display_order);
```

### Кеширование

Настройте кеширование на уровне веб-сервера для статических файлов.

Apache `.htaccess`:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/json "access plus 0 seconds"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
</IfModule>
```

## 📚 Дополнительные ресурсы

- [README.md](README.md) - Общее описание
- [QUICKSTART.md](QUICKSTART.md) - Быстрый старт
- [DEPLOYMENT.md](DEPLOYMENT.md) - Детальное развертывание
- [docs/AUTHENTICATION.md](docs/AUTHENTICATION.md) - Аутентификация
- [docs/TELEGRAM_INTEGRATION.md](docs/TELEGRAM_INTEGRATION.md) - Telegram бот

## ✅ Checklist финальной проверки

- [ ] Composer зависимости установлены (`vendor/` существует)
- [ ] База данных создана и схема импортирована
- [ ] Администратор создан и может войти
- [ ] `.env` настроен с правильными значениями
- [ ] `JWT_SECRET` изменен на случайную строку
- [ ] `APP_DEBUG=false` в production
- [ ] Права доступа на `storage/` установлены (775)
- [ ] `.htaccess` / nginx конфигурация работает
- [ ] `test-setup.php` проходит все проверки
- [ ] `test-db.php` успешно подключается к БД
- [ ] `test-routes.php` показывает все endpoints работающими
- [ ] `/api/health` возвращает JSON с status: "healthy"
- [ ] HTTPS настроен и работает
- [ ] CORS настроен для фронтенда
- [ ] Фронтенд `meta` тег `api-base-url` указывает на API
- [ ] Тестовый логин в админ-панель работает
- [ ] Telegram интеграция настроена (опционально)

## 🎉 Готово!

Если все проверки пройдены, ваш бекенд успешно развернут и готов к работе!

**Доступ к API:**
- Health Check: `https://yourdomain.com/backend/public/api/health`
- Admin Login: `https://yourdomain.com/admin.html`

**Следующие шаги:**
1. Войдите в админ-панель
2. Измените пароль администратора
3. Настройте контент сайта
4. Настройте калькулятор
5. Настройте Telegram уведомления (опционально)

---

**Поддержка:** Если возникли проблемы, см. раздел "Устранение неполадок" выше.
