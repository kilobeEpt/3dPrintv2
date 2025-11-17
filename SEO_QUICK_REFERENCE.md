# 🚀 SEO Quick Reference Card

## 📁 Files Location
```
/robots.txt                         # Robots configuration
/sitemap.xml                        # Auto-generated sitemap
/tools/generate-sitemap.py          # Python generator
/tools/generate-sitemap.php         # PHP generator
/tools/verify-seo-setup.sh          # Verification script
/docs/seo/sitemap-robots.md         # Full documentation (21KB)
/docs/seo/QUICKSTART.md             # Quick guide
```

## ⚡ Common Commands

### Regenerate Sitemap
```bash
python3 tools/generate-sitemap.py https://3dprint-omsk.ru
```

### Verify Setup
```bash
./tools/verify-seo-setup.sh
```

### Deploy (Auto-generates sitemap)
```bash
cd backend && ./deploy.sh
```

### Check Production
```bash
curl https://3dprint-omsk.ru/robots.txt
curl https://3dprint-omsk.ru/sitemap.xml
```

## 📊 Current Sitemap Structure

| URL | Priority | Frequency |
|-----|----------|-----------|
| `/` | 1.0 | daily |
| `/#home` | 1.0 | daily |
| `/#services` | 0.9 | weekly |
| `/#calculator` | 0.8 | weekly |
| `/#portfolio` | 0.9 | weekly |
| `/#about` | 0.7 | monthly |
| `/#contact` | 0.8 | monthly |

**Total URLs**: 7

## 🔒 Blocked Areas (robots.txt)
- `/admin.html` - Admin panel
- `/backend/` - API backend
- `/backend_old/` - Old backend
- `/docs/` - Documentation
- `/tools/` - Scripts
- `*.md` - Markdown files
- `/config.js` - Configuration

## 🔗 Important Links

### Search Consoles
- [Google Search Console](https://search.google.com/search-console)
- [Yandex Webmaster](https://webmaster.yandex.ru/)

### Validators
- [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)
- [Robots.txt Tester](https://www.google.com/webmasters/tools/robots-testing-tool)

### Documentation
- [Full Guide](docs/seo/sitemap-robots.md)
- [Quick Start](docs/seo/QUICKSTART.md)
- [Test Checklist](docs/test-checklist.md) - Section 11

## ✏️ Adding New Section

1. Edit `tools/generate-sitemap.py`:
   ```python
   {
       'loc': f'{base_url}/#newsection',
       'changefreq': 'weekly',
       'priority': '0.8'
   }
   ```
2. Run: `python3 tools/generate-sitemap.py`
3. Deploy: `cd backend && ./deploy.sh`
4. Resubmit to search consoles

## 🆘 Troubleshooting

**Sitemap not updating?**
```bash
python3 tools/generate-sitemap.py https://3dprint-omsk.ru
```

**Need help?**
- See: [docs/seo/sitemap-robots.md](docs/seo/sitemap-robots.md)
- Check: [docs/test-checklist.md](docs/test-checklist.md) Section 11

---

**Status**: ✅ Production Ready  
**Last Updated**: 2025-11-17
