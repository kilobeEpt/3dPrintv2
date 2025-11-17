# SEO Метаданные - Быстрый старт

**📚 Полная документация:** [meta-guidelines.md](./meta-guidelines.md)

---

## 🚀 Быстрое обновление метаданных

### 1. Открыть конфиг

```bash
nano config/seo-metadata.json
```

### 2. Найти секцию

```json
"pages": {
  "home": {
    "title": "Новый заголовок",
    "description": "Новое описание"
  }
}
```

### 3. Сохранить и загрузить

```bash
# Валидация
cat config/seo-metadata.json | jq .

# Загрузка
scp config/seo-metadata.json user@server:/path/to/config/
```

### 4. Проверить

Открыть сайт → F12 → Console:
```javascript
seoManager.validate()
```

---

## 📏 Рекомендуемые размеры

| Элемент | Длина | Оптимально |
|---------|-------|------------|
| Title | 30-60 символов | 50-55 |
| Description | 120-160 символов | 150-155 |
| OG Title | До 95 символов | 60-70 |
| OG Description | До 200 символов | 150-160 |
| OG Image | 1200x630px | JPG |
| Twitter Image | 1200x675px | JPG |

---

## ✅ Чек-лист перед публикацией

- [ ] JSON валиден (jsonlint.com)
- [ ] Title: 30-60 символов
- [ ] Description: 120-160 символов
- [ ] Canonical URL корректен (HTTPS)
- [ ] OG изображения доступны
- [ ] Дата lastUpdated обновлена
- [ ] `seoManager.validate()` без ошибок
- [ ] Проверено на [metatags.io](https://metatags.io/)

---

## 🛠 Консольные команды

```javascript
// Валидация
seoManager.validate()

// Текущие метаданные
seoManager.exportCurrentMetadata()

// Текущая секция
seoManager.getCurrentSection()

// Обновить секцию вручную
seoManager.updatePageMetadata('services')
```

---

## 🌐 Инструменты проверки

- [Meta Tags Preview](https://metatags.io/)
- [Google Rich Results](https://search.google.com/test/rich-results)
- [Open Graph Check](https://opengraphcheck.com/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)

---

## 📞 Поддержка

**Проблемы?**
1. Проверить консоль (F12)
2. Запустить `seoManager.validate()`
3. Проверить JSON на jsonlint.com
4. См. [meta-guidelines.md](./meta-guidelines.md)

---

**Версия:** 1.0.0  
**Обновлено:** 2024-01-15
