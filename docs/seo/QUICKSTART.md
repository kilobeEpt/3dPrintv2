# SEO Quick Start Guide

Quick reference for managing sitemap and robots.txt.

## 🚀 Quick Commands

### Generate Sitemap
```bash
# Python (recommended)
python3 tools/generate-sitemap.py https://3dprint-omsk.ru

# PHP (fallback)
php tools/generate-sitemap.php https://3dprint-omsk.ru

# Custom URL
python3 tools/generate-sitemap.py https://example.com
```

### Deploy with Sitemap Update
```bash
cd backend
./deploy.sh
# Automatically generates sitemap during deployment
```

### Validate Files
```bash
# Check XML syntax
xmllint --noout sitemap.xml && echo "✅ Valid XML"

# Check accessibility (production)
curl -I https://3dprint-omsk.ru/robots.txt
curl -I https://3dprint-omsk.ru/sitemap.xml
```

## 📋 File Locations

```
/robots.txt                    # Robots configuration
/sitemap.xml                   # Generated sitemap
/tools/generate-sitemap.py     # Python generator
/tools/generate-sitemap.php    # PHP generator
/docs/seo/sitemap-robots.md    # Full documentation
```

## 🔄 Common Tasks

### Add New Section
1. Edit `tools/generate-sitemap.py`
2. Add new URL to `urls` array:
   ```python
   {
       'loc': f'{base_url}/#newsection',
       'changefreq': 'weekly',
       'priority': '0.8'
   }
   ```
3. Run: `python3 tools/generate-sitemap.py https://3dprint-omsk.ru`
4. Commit and deploy
5. Resubmit to search consoles

### Update Robots.txt
1. Edit `/robots.txt`
2. Add/modify Allow or Disallow directives
3. Test with [Google Robots Testing Tool](https://www.google.com/webmasters/tools/robots-testing-tool)
4. Commit and deploy

### Check Indexing Status
```bash
# Google search
site:3dprint-omsk.ru

# Specific section
site:3dprint-omsk.ru#services
```

## 🔗 Important Links

- **Full Documentation**: [docs/seo/sitemap-robots.md](sitemap-robots.md)
- **Google Search Console**: https://search.google.com/search-console
- **Yandex Webmaster**: https://webmaster.yandex.ru/
- **XML Validator**: https://www.xml-sitemaps.com/validate-xml-sitemap.html

## 📊 Current Structure

| URL | Priority | Frequency |
|-----|----------|-----------|
| / | 1.0 | daily |
| /#home | 1.0 | daily |
| /#services | 0.9 | weekly |
| /#calculator | 0.8 | weekly |
| /#portfolio | 0.9 | weekly |
| /#about | 0.7 | monthly |
| /#contact | 0.8 | monthly |

## ⚠️ Important Notes

- ✅ Sitemap auto-updates on deploy
- ✅ robots.txt blocks admin and backend
- ✅ Always test after changes
- ✅ Resubmit to search consoles after updates

## 🆘 Troubleshooting

**Sitemap not updating?**
```bash
python3 tools/generate-sitemap.py https://3dprint-omsk.ru
```

**robots.txt issues?**
- Check syntax with Google's testing tool
- Verify file is accessible (curl)
- Ensure proper line endings (LF, not CRLF)

**Need help?**
- See full documentation: `docs/seo/sitemap-robots.md`
- Check test checklist: `docs/test-checklist.md` section 11
