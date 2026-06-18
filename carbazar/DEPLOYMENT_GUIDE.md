# 🚀 CarBazar - FREE Hosting Deployment Guide

## ✅ Step-by-Step Setup (InfinityFree)

### 1️⃣ Create InfinityFree Account
- Go to: https://infinityfree.net
- Click "Sign Up"
- Enter email & password
- Verify email

### 2️⃣ Create Hosting Account
- Login to InfinityFree
- Click "Create Account"
- Choose subdomain: `carbazar.infinityfreeapp.com`
- Wait 2-3 minutes for activation

### 3️⃣ Create MySQL Database
- Control Panel → MySQL Databases
- Click "Create Database"
- Database name: `carbazar`
- **SAVE THESE DETAILS:**
  ```
  Database Name: epiz_XXXXXXXX_carbazar
  Database User: epiz_XXXXXXXX
  Database Password: [generated password]
  Database Host: sqlXXX.infinityfree.net
  ```

### 4️⃣ Upload Files

#### Option A: File Manager (Easy)
1. Control Panel → Online File Manager
2. Open `htdocs` folder
3. Upload all project files
4. Wait for upload to complete

#### Option B: FTP (Recommended - Faster)
1. Download FileZilla: https://filezilla-project.org
2. Get FTP details from InfinityFree Control Panel
3. Connect using:
   ```
   Host: ftpupload.net
   Username: epiz_XXXXXXXX
   Password: [your password]
   Port: 21
   ```
4. Upload all files to `htdocs` folder

### 5️⃣ Import Database
1. Control Panel → phpMyAdmin
2. Select your database (left sidebar)
3. Click "Import" tab
4. Choose file: `database.sql`
5. Click "Go"
6. Wait for success message ✅

### 6️⃣ Update Database Configuration

Edit file: `backend/config/db.php`

**REPLACE THIS:**
```php
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'carbazar');
```

**WITH THIS (use your actual details):**
```php
if (!defined('DB_HOST')) define('DB_HOST', 'sqlXXX.infinityfree.net');
if (!defined('DB_USER')) define('DB_USER', 'epiz_XXXXXXXX');
if (!defined('DB_PASS')) define('DB_PASS', 'your_password_here');
if (!defined('DB_NAME')) define('DB_NAME', 'epiz_XXXXXXXX_carbazar');
```

### 7️⃣ Update Base URL

Edit file: `backend/config/db.php`

**REPLACE THIS:**
```php
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/carbazar/');
```

**WITH THIS:**
```php
if (!defined('BASE_URL')) define('BASE_URL', 'http://carbazar.infinityfreeapp.com/');
```

### 8️⃣ Update SEO URLs

Edit file: `includes/seo.php`

**REPLACE ALL instances of:**
```php
'site_url' => 'http://localhost/carbazar/',
```

**WITH:**
```php
'site_url' => 'http://carbazar.infinityfreeapp.com/',
```

### 9️⃣ Update Sitemap

Edit file: `sitemap.xml`

**REPLACE ALL instances of:**
```xml
http://localhost/carbazar/
```

**WITH:**
```xml
http://carbazar.infinityfreeapp.com/
```

### 🔟 Test Your Website

1. Open browser
2. Go to: `http://carbazar.infinityfreeapp.com`
3. Test these pages:
   - ✅ Homepage loads
   - ✅ Register new account
   - ✅ Login works
   - ✅ Browse cars
   - ✅ Browse spare parts
   - ✅ Add to cart
   - ✅ Wishlist works

---

## 🎯 Admin Panel Access

**URL:** `http://carbazar.infinityfreeapp.com/backend/admin/login.php`

**Default Credentials:**
```
Email: admin@carbazar.com
Password: admin123
```

**⚠️ IMPORTANT:** Change admin password immediately after first login!

---

## 📁 File Structure on Server

```
htdocs/
├── index.php
├── all-cars.php
├── all-parts.php
├── car-detail.php
├── cart.php
├── wishlist.php
├── orders.php
├── sell.php
├── login.php
├── register.php
├── contact.php
├── about-us.php
├── reviews.php
├── my-transactions.php
├── styles.css
├── sitemap.xml
├── robots.txt
├── backend/
│   ├── config/
│   │   └── db.php (UPDATE THIS!)
│   ├── api/
│   ├── admin/
│   ├── seller/
│   ├── auth/
│   └── logs/
├── includes/
│   ├── navbar.php
│   └── seo.php (UPDATE THIS!)
└── uploads/
    └── (car & product images)
```

---

## 🔧 Common Issues & Solutions

### Issue 1: "Database Connection Failed"
**Solution:**
- Check `backend/config/db.php` has correct details
- Verify database exists in phpMyAdmin
- Check database host (sqlXXX.infinityfree.net)

### Issue 2: "404 Not Found"
**Solution:**
- Ensure files are in `htdocs` folder (not htdocs/carbazar)
- Check file names are correct (case-sensitive)

### Issue 3: "Images Not Loading"
**Solution:**
- Upload `uploads/` folder with all images
- Check file permissions (755 for folders, 644 for files)

### Issue 4: "Session Errors"
**Solution:**
- InfinityFree supports sessions by default
- Clear browser cache and cookies
- Try incognito/private mode

### Issue 5: "Email Not Sending"
**Solution:**
- InfinityFree blocks mail() function
- Use SMTP instead (PHPMailer)
- Or disable email features temporarily

---

## 🌐 Optional: Connect Custom Domain (FREE)

### Get FREE Domain from Freenom

1. Go to: https://freenom.com
2. Search: `carbazar`
3. Select: `.tk`, `.ml`, `.ga`, or `.cf` (all FREE)
4. Checkout (0.00 USD)
5. Register for 12 months FREE

### Connect to InfinityFree

1. InfinityFree → Add Domain
2. Enter: `carbazar.tk`
3. Get nameservers:
   ```
   ns1.byet.org
   ns2.byet.org
   ```
4. Freenom → Manage Domain → Management Tools → Nameservers
5. Select "Use custom nameservers"
6. Enter:
   ```
   Nameserver 1: ns1.byet.org
   Nameserver 2: ns2.byet.org
   ```
7. Save changes
8. Wait 24-48 hours for DNS propagation

---

## 📊 Performance Tips

### 1. Enable Cloudflare (FREE CDN)
- Go to: https://cloudflare.com
- Add your domain
- Change nameservers to Cloudflare
- Enable caching & minification

### 2. Optimize Images
- Use TinyPNG: https://tinypng.com
- Compress all images before upload
- Target: < 200 KB per image

### 3. Enable Gzip Compression
Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 4. Browser Caching
Add to `.htaccess`:
```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## 🔒 Security Checklist

- [ ] Change admin password
- [ ] Update database credentials
- [ ] Remove `database.sql` from public folder
- [ ] Set proper file permissions
- [ ] Enable HTTPS (via Cloudflare)
- [ ] Backup database weekly
- [ ] Monitor error logs

---

## 📞 Support

**InfinityFree Forum:** https://forum.infinityfree.net
**Documentation:** https://infinityfree.net/support

---

## 🎉 Congratulations!

Your CarBazar website is now LIVE on the internet! 🚀

**Share your link:**
- WhatsApp
- Facebook
- Instagram
- Twitter

**Next Steps:**
1. Add real car listings
2. Invite sellers to register
3. Promote on social media
4. Submit to Google Search Console
5. Monitor traffic with Google Analytics

---

**Made with ❤️ by CarBazar Team**
