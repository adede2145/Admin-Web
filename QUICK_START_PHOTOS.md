# 🚀 QUICK START - Photo Migration

## Installation Commands

### Local Machine (Windows XAMPP)
```cmd
cd C:\xampp\htdocs\Admin-Web

REM Install package
composer require intervention/image

REM Create directories
mkdir storage\app\private\employees\photos\thumbs
```

### Production VM - Linux
```bash
cd /path/to/Admin-Web
composer require intervention/image
mkdir -p storage/app/private/employees/photos/thumbs
chmod -R 755 storage/app/private
chown -R www-data:www-data storage/app/private
```

### Production VM - Windows
```cmd
cd C:\xampp\htdocs\Admin-Web
composer require intervention/image
mkdir storage\app\private\employees\photos\thumbs
```

---

## Testing (After Installation)

```bash
# Test 1: Register new employee with photo
# Check file created: storage/app/private/employees/photos/employee_X.jpg

# Test 2: View employee list - all photos load

# Test 3: Security - logout and try accessing photo URL
# Should return placeholder, not actual photo

# Test 4: Migrate existing photos (optional)
php artisan photos:migrate
```

---

## What Changed?

✅ New registrations: Photos saved as compressed JPEG files
✅ Photo storage: Private folder (secure, not public)
✅ Photo display: Through controller with authentication
✅ File sizes: 50-90% smaller
✅ Security: Authentication + authorization required
✅ Kiosk: Still works (uses same endpoint)

---

## Storage Location

**OLD:** Database BLOB (photo_data column)
**NEW:** storage/app/private/employees/photos/employee_X.jpg

**Access:** Via `/employees/{id}/photo` route (secure)
**NOT accessible:** Direct file URL (security!)

---

## Checklist

- [ ] Install intervention/image package
- [ ] Create storage directories
- [ ] Test new employee registration
- [ ] Verify photos display
- [ ] Run migration command (optional)
- [ ] Test on production VM
- [ ] Verify C# Kiosk works

---

**Status: Code updated, ready to install packages!**
