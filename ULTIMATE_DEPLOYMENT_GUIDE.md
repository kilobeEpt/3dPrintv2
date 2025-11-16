# 🚀 ULTIMATE DEPLOYMENT GUIDE
## Полное руководство по развертыванию 3D Print Pro

**Дата:** 2024-11-15  
**Версия:** STANDALONE (Без Composer)  
**Hosting:** Timeweb (ch167436.tw1.ru)

---

## 📋 СОДЕРЖАНИЕ

1. [Быстрый старт](#быстрый-старт)
2. [Проблемы и решения](#проблемы-и-решения)
3. [Standalone режим](#standalone-режим)
4. [Пошаговая инструкция](#пошаговая-инструкция)
5. [Проверка работоспособности](#проверка-работоспособности)
6. [Troubleshooting](#troubleshooting)
7. [Финальная проверка](#финальная-проверка)

---

## ⚡ БЫСТРЫЙ СТАРТ

### Для опытных пользователей:

```bash
# 1. Загрузить файлы на хостинг
# 2. Настроить .env
# 3. Импортировать database/migrations/20231113_initial.sql
# 4. Создать администратора:
php backend/database/seeds/seed-admin-user.php

# 5. Переключиться на standalone режим:
cd backend/public
mv .htaccess .htaccess-composer-backup
mv .htaccess-standalone .htaccess
mv index.php index-composer-backup.php
mv index-standalone.php index.php

# 6. Запустить проверку:
php backend/ultimate-final-check.php https://ch167436.tw1.ru
```

---

## 🔥 ПРОБЛЕМЫ И РЕШЕНИЯ

### Проблема #1: API возвращает 301/302 редиректы

**Симптомы:**
- GET /api/health → 302 Found
- POST /api/auth/login → 404 Not Found
- Админ панель не может авторизоваться

**Причина:** Неправильная конфигурация .htaccess или RewriteBase

**Решение:**
1. Проверить, что в .htaccess НЕТ флагов R=301 или R=302
2. Настроить правильный RewriteBase:
   - Если API в корне: `RewriteBase /`
   - Если в подпапке: `RewriteBase /backend/public/`
3. Использовать standalone версию (см. ниже)

---

### Проблема #2: vendor/ не установлена (Composer missing)

**Симптомы:**
- "Composer dependencies not installed"
- Fatal error: Class 'Slim\Factory\AppFactory' not found

**Причина:** На хостинге нет Composer или не установлены зависимости

**Решение:** Использовать STANDALONE режим (без Composer)

---

### Проблема #3: Фронтенд не может подключиться к API

**Симптомы:**
- Админ панель показывает "Failed to fetch"
- Console errors: CORS, 404, network errors

**Причина:** Неправильный API_BASE_URL или CORS

**Решение:**
1. Проверить meta tag в admin.html:
   ```html
   <meta name="api-base-url" content="">
   ```
2. Если API в подпапке, указать полный путь:
   ```html
   <meta name="api-base-url" content="/backend/public">
   ```

---

## 🛠️ STANDALONE РЕЖИМ

### Что это?

Standalone режим - это версия API, которая **НЕ ТРЕБУЕТ Composer** и работает с простыми PHP библиотеками.

### Преимущества:

✅ Не нужен Composer  
✅ Не нужна vendor/  
✅ Работает на любом хостинге с PHP 7.4+  
✅ Легкий в развертывании  
✅ Все функции доступны  

### Что заменяется:

| Composer Package | Standalone Замена |
|-----------------|-------------------|
| slim/slim | SimpleRouter.php |
| vlucas/phpdotenv | SimpleEnv.php |
| firebase/php-jwt | SimpleJWT.php |
| PSR-4 Autoload | autoload.php |

### Файлы:

```
backend/
├── standalone/
│   ├── autoload.php      # Простой PSR-4 автозагрузчик
│   ├── SimpleEnv.php     # .env парсер
│   ├── SimpleJWT.php     # JWT encoding/decoding
│   └── SimpleRouter.php  # HTTP роутер
├── public/
│   ├── index-standalone.php  # Standalone entry point
│   └── .htaccess-standalone  # Конфигурация для standalone
```

---

## 📝 ПОШАГОВАЯ ИНСТРУКЦИЯ

### Шаг 1: Подготовка файлов

1. **Скачать все файлы проекта**
2. **Проверить структуру:**
   ```
   /
   ├── index.html
   ├── admin.html
   ├── css/
   ├── js/
   ├── backend/
   │   ├── public/
   │   │   ├── index-standalone.php
   │   │   └── .htaccess-standalone
   │   ├── standalone/
   │   ├── src/
   │   ├── database/
   │   └── .env.example
   ```

### Шаг 2: Загрузка на хостинг

**Через FTP/SFTP:**

1. Подключиться к хостингу
2. Загрузить все файлы в корень сайта
3. Убедиться, что права доступа:
   - Файлы: 644
   - Папки: 755
   - backend/storage/: 775

**Через SSH:**

```bash
# Подключиться
ssh username@ch167436.tw1.ru

# Загрузить файлы
scp -r /path/to/project/* username@ch167436.tw1.ru:/path/to/site/
```

### Шаг 3: Настройка базы данных

1. **Создать базу данных** (через панель хостинга)
   - Имя: `ch167436_3dprint` (или другое)
   - Кодировка: `utf8mb4_unicode_ci`

2. **Импортировать схему:**
   ```bash
   mysql -u username -p ch167436_3dprint < backend/database/migrations/20231113_initial.sql
   ```

   Или через phpMyAdmin:
   - Открыть phpMyAdmin
   - Выбрать базу данных
   - Вкладка "Импорт"
   - Выбрать файл `20231113_initial.sql`
   - Нажать "Вперед"

3. **Импортировать начальные данные:**
   ```bash
   mysql -u username -p ch167436_3dprint < backend/database/seeds/initial_data.sql
   ```

### Шаг 4: Настройка .env

1. **Скопировать .env.example:**
   ```bash
   cd backend
   cp .env.example .env
   ```

2. **Отредактировать .env:**
   ```env
   # Database
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=ch167436_3dprint
   DB_USERNAME=ch167436_admin
   DB_PASSWORD=your_db_password

   # Application
   APP_ENV=production
   APP_DEBUG=false

   # JWT
   JWT_SECRET=your_very_long_random_secret_key_here_64_chars_minimum

   # CORS
   CORS_ORIGIN=https://ch167436.tw1.ru
   ```

3. **Сгенерировать JWT секрет:**
   ```bash
   openssl rand -base64 64
   ```

### Шаг 5: Создание администратора

```bash
cd backend/database/seeds
php seed-admin-user.php
```

Или вручную:

```sql
INSERT INTO users (login, password, name, email, role, active, created_at)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123456
    'Administrator',
    'admin@example.com',
    'admin',
    1,
    NOW()
);
```

### Шаг 6: Переключение на Standalone режим

```bash
cd backend/public

# Бэкап старых файлов (если есть)
mv .htaccess .htaccess-composer-backup
mv index.php index-composer-backup.php

# Активировать standalone
cp .htaccess-standalone .htaccess
cp index-standalone.php index.php
```

### Шаг 7: Настройка RewriteBase

Открыть `backend/public/.htaccess` и настроить RewriteBase:

**Вариант A: API в корне** (рекомендуется)
```apache
RewriteBase /
```

**Вариант B: API в подпапке**
```apache
RewriteBase /backend/public/
```

**Как определить:**
- Если API доступен по `https://ch167436.tw1.ru/api/health` → используйте `/`
- Если API доступен по `https://ch167436.tw1.ru/backend/public/api/health` → используйте `/backend/public/`

### Шаг 8: Настройка фронтенда

Открыть `admin.html` и найти:

```html
<meta name="api-base-url" content="">
```

**Вариант A: API в корне**
```html
<meta name="api-base-url" content="">
```

**Вариант B: API в подпапке**
```html
<meta name="api-base-url" content="/backend/public">
```

То же самое для `index.html`.

### Шаг 9: Проверка .htaccess

Убедиться, что `.htaccess` **НЕ содержит** флагов редиректа:

```apache
# ❌ ПЛОХО - вызывает редиректы
RewriteRule ^ index.php [R=301,QSA,L]

# ✅ ХОРОШО - внутреннее переписывание
RewriteRule ^ index.php [QSA,L]
```

---

## ✅ ПРОВЕРКА РАБОТОСПОСОБНОСТИ

### Быстрая проверка (через браузер):

1. **Открыть:** `https://ch167436.tw1.ru/api/health`
   - **Ожидается:** JSON с `{"success": true, "mode": "standalone"}`
   - **НЕ должно быть:** 301, 302, 404

2. **Открыть:** `https://ch167436.tw1.ru/admin.html`
   - **Войти:** admin / admin123456
   - **Ожидается:** Успешная авторизация
   - **НЕ должно быть:** 404 на /api/auth/login

3. **Проверить:** Dashboard, Orders, Services
   - Все должно загружаться без ошибок

### Полная проверка (через CLI):

```bash
php backend/ultimate-final-check.php https://ch167436.tw1.ru
```

**Вывод:**
```
═══════════════════════════════════════════════════
   ULTIMATE FINAL DEPLOYMENT CHECK
═══════════════════════════════════════════════════

[1] CRITICAL CHECKS - NO REDIRECTS
───────────────────────────────────────────────────
API root - no redirect                             [✓ PASS] (Status: 404)
Health endpoint - no redirect                      [✓ PASS] (Returns 200)
Auth endpoint - no redirect                        [✓ PASS] (Status: 422)

[2] API HEALTH & DATABASE
───────────────────────────────────────────────────
Health endpoint returns JSON                       [✓ PASS] (Mode: standalone)
Database connection                                [✓ PASS]

[3] AUTHENTICATION
───────────────────────────────────────────────────
Login endpoint exists                              [✓ PASS] (Status: 422)
Login with invalid credentials                     [✓ PASS]
Login with valid credentials                       [✓ PASS] (Token received)
Protected endpoint without auth                    [✓ PASS]
Protected endpoint with auth                       [✓ PASS]

...

═══════════════════════════════════════════════════
   RESULTS
═══════════════════════════════════════════════════
Total Tests:  25
Passed:       25
Failed:       0
Success Rate: 100.0%

═══════════════════════════════════════════════════
   ✓ ALL TESTS PASSED - READY FOR PRODUCTION!
═══════════════════════════════════════════════════
```

---

## 🔧 TROUBLESHOOTING

### Ошибка: "Composer dependencies not installed"

**Решение:**
```bash
cd backend/public
mv index.php index-composer-backup.php
cp index-standalone.php index.php
```

---

### Ошибка: 301/302 Redirect

**Диагностика:**
```bash
curl -I https://ch167436.tw1.ru/api/health
```

**Если видите:**
```
HTTP/1.1 301 Moved Permanently
Location: /api/health/
```

**Решение:**
1. Открыть `.htaccess`
2. Убедиться, что НЕТ строк:
   ```apache
   RewriteCond %{REQUEST_URI} (.+)/$
   RewriteRule ^ %1 [L,R=301]
   ```
3. Проверить RewriteBase
4. Перезагрузить Apache (если есть доступ)

---

### Ошибка: 404 Not Found

**Возможные причины:**

1. **RewriteBase неправильный:**
   ```apache
   # Попробовать разные варианты:
   RewriteBase /
   # или
   RewriteBase /backend/public/
   ```

2. **.htaccess не работает:**
   ```bash
   # Проверить, читается ли .htaccess
   echo "Invalid syntax here" >> .htaccess
   # Если получили 500 Internal Server Error - значит читается
   # Восстановить файл
   ```

3. **mod_rewrite не включен:**
   ```apache
   # В .htaccess добавить в начало:
   <IfModule !mod_rewrite.c>
       <IfModule mod_actions.c>
           Action application/x-httpd-php /backend/public/index.php
       </IfModule>
   </IfModule>
   ```

---

### Ошибка: Database connection failed

**Решение:**
1. Проверить credentials в `.env`
2. Проверить, что база данных создана
3. Проверить права пользователя:
   ```sql
   GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'ch167436_admin'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

### Ошибка: CORS

**Симптомы:**
```
Access to fetch at 'https://...' from origin 'https://...' has been blocked by CORS policy
```

**Решение:**

В `.htaccess` добавить:
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

Или в `.env`:
```env
CORS_ORIGIN=*
```

---

### Ошибка: Token expired / Invalid token

**Решение:**
1. Очистить localStorage в браузере
2. Выйти и войти снова
3. Проверить JWT_SECRET в `.env`
4. Проверить время на сервере:
   ```bash
   date
   ```

---

### Ошибка: 500 Internal Server Error

**Диагностика:**
```bash
# Включить вывод ошибок
echo "APP_DEBUG=true" >> backend/.env

# Посмотреть логи
tail -f backend/storage/logs/app.log

# Логи веб-сервера
tail -f /var/log/apache2/error.log
```

---

## 🎯 ФИНАЛЬНАЯ ПРОВЕРКА

### Чеклист перед запуском:

- [ ] База данных создана и импортирована
- [ ] .env настроен с правильными credentials
- [ ] JWT_SECRET сгенерирован и установлен
- [ ] Администратор создан
- [ ] Standalone режим активирован
- [ ] RewriteBase настроен правильно
- [ ] Фронтенд api-base-url настроен
- [ ] Права доступа 644/755 установлены
- [ ] ultimate-final-check.php показывает 100%
- [ ] /api/health возвращает 200 (не 301/302)
- [ ] Админ панель логинится успешно
- [ ] Все CRUD операции работают

### Тест в production:

```bash
# 1. Проверка API
curl https://ch167436.tw1.ru/api/health

# 2. Проверка авторизации
curl -X POST https://ch167436.tw1.ru/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123456"}'

# 3. Полный тест
php backend/ultimate-final-check.php https://ch167436.tw1.ru
```

### Все тесты зелёные?

🎉 **ПОЗДРАВЛЯЕМ! Развертывание завершено успешно!**

---

## 📞 ПОДДЕРЖКА

### Если ничего не помогло:

1. **Проверить логи:**
   ```bash
   tail -100 backend/storage/logs/app.log
   tail -100 /var/log/apache2/error.log
   ```

2. **Запустить диагностику:**
   ```bash
   php backend/diagnose.php
   php backend/test-setup.php
   php backend/test-db.php
   php backend/test-routes.php
   ```

3. **Проверить PHP версию:**
   ```bash
   php -v  # Должно быть >= 7.4
   ```

4. **Проверить расширения:**
   ```bash
   php -m | grep -E 'pdo|json|mbstring|curl'
   ```

### Контакты:

- **Документация:** См. `backend/README.md`
- **Troubleshooting:** См. `backend/TROUBLESHOOTING.md`
- **Quick Reference:** См. `backend/QUICK_REFERENCE.md`

---

## 📚 ДОПОЛНИТЕЛЬНЫЕ МАТЕРИАЛЫ

### Важные файлы:

- `backend/README.md` - Полная документация API
- `backend/DEPLOYMENT_GUIDE.md` - Расширенное руководство
- `backend/TROUBLESHOOTING.md` - Решение проблем
- `backend/QUICK_REFERENCE.md` - Быстрая справка
- `backend/docs/AUTHENTICATION.md` - Документация по авторизации
- `backend/docs/TELEGRAM_INTEGRATION.md` - Настройка Telegram

### Полезные скрипты:

- `backend/ultimate-final-check.php` - Финальная проверка
- `backend/diagnose.php` - Диагностика
- `backend/test-routes.php` - Тест роутов
- `backend/test-db.php` - Тест базы данных
- `backend/bin/reset-password.php` - Сброс пароля

---

**Версия:** 1.0.0  
**Последнее обновление:** 2024-11-15  
**Автор:** 3D Print Pro Team

---

## 🔒 БЕЗОПАСНОСТЬ

### Перед запуском в production:

1. **Изменить credentials:**
   - Пароль администратора
   - JWT_SECRET
   - Database password

2. **Отключить debug:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Настроить CORS:**
   ```env
   CORS_ORIGIN=https://ch167436.tw1.ru
   ```

4. **Включить HTTPS:**
   - Получить SSL сертификат (Let's Encrypt)
   - Настроить редирект HTTP → HTTPS

5. **Защитить файлы:**
   ```bash
   chmod 644 backend/.env
   chmod 755 backend/storage
   ```

6. **Проверить .htaccess:**
   ```apache
   <FilesMatch "(\.env|composer\.json|composer\.lock)$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

---

**🎯 ГОТОВО К РАБОТЕ!**
