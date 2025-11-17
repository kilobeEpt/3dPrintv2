# 🚀 QUICK START - 3D Print Backend

## ⚡ 5-Minute Deployment Guide

### Step 1: Upload Files (2 min)

Upload the `backend/` folder to your server:

```
Local: /your/project/backend/
Server: /home/c/ch167436/3dPrint/public_html/backend/
```

### Step 2: Import Database (1 min)

```bash
cd /home/c/ch167436/3dPrint/public_html/backend
mysql -u ch167436 -p852789456 ch167436_3dprint < database/migrations/20231113_initial.sql
```

### Step 3: Run Deployment Check (30 sec)

```bash
./deploy.sh
```

Expected: ✅ All checks passed! Backend is ready.

### Step 4: Test Everything (1 min)

```bash
./test-all.php https://3dprint-omsk.ru/backend/public
```

Expected: ✅ ALL TESTS PASSED - SYSTEM READY!

### Step 5: Login (30 sec)

Open: https://3dprint-omsk.ru/admin.html

**Credentials:**
- Login: `admin`
- Password: `admin123`

⚠️ **Change password immediately after login!**

---

## ✅ Done! Your system is ready.

**What you have now:**
- ✅ Simple, dependency-free PHP backend
- ✅ All API endpoints working
- ✅ JWT authentication
- ✅ Admin panel access
- ✅ Database connected
- ✅ 100% tested and verified

**Next steps:**
1. Change admin password
2. Add your content via admin panel
3. Configure Telegram bot (optional)
4. Test order form on website

---

## 📚 Need More Info?

- **Complete documentation:** See `backend/README_NEW.md`
- **Full summary:** See `COMPLETE_REWRITE_SUMMARY.md`
- **Troubleshooting:** Run `./deploy.sh` or `./test-all.php`

---

## 🆘 Quick Troubleshooting

**Problem:** Tests fail  
**Solution:** Check database credentials in `.env`

**Problem:** 404 errors  
**Solution:** Check `.htaccess` exists in `public/` folder

**Problem:** Can't login  
**Solution:** Run `php create-admin.php` to recreate admin user

**Problem:** Frontend can't connect  
**Solution:** Check `js/admin-api-client.js` and `js/apiClient.js` have correct baseURL

---

## 🎉 Success!

You now have a fully functional 3D printing service backend with admin panel!
