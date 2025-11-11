# Employee Photos - Blob Storage System

## 📦 Overview

Employee photos are stored as **compressed blobs in the database** - no file storage needed!

- **Storage:** Database only (`photo_data` column)
- **Size:** ~50-150 KB per photo
- **Quality:** 70% JPEG, max 800px width
- **Performance:** Fast loading, cached for 24 hours

---

## 🚀 API Endpoints

### Get Photo (Compressed Blob)
```
GET /api/employee-photos/{employeeId}
```
- Returns compressed JPEG from database
- Public endpoint (no auth required)
- Cache headers: 24 hours

**Example:**
```javascript
<img src="/api/employee-photos/123" alt="Employee" />
```

### Upload Photo
```
POST /api/employee-photos/{employeeId}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

photo: (image file)
```
- Requires authentication
- Max file size: 10 MB
- Accepts: JPG, PNG, GIF
- Automatically compresses to blob

---

## 📝 Registration & Employee Management

**All employee creation/update methods now use blob storage:**

### 1. Registration API (`/api/register-employee`)
- Accepts `profile_image` file
- Automatically compresses to blob
- Stores in `photo_data` column
- Sets `photo_path` to `NULL`

### 2. Admin Employee Creation (`EmployeeController@store`)
- Accepts `profile_image` file
- Automatically compresses to blob
- Stores in `photo_data` column
- Sets `photo_path` to `NULL`

### 3. Admin Employee Update (`EmployeeController@update`)
- Accepts new `profile_image` file
- Automatically compresses to blob
- Updates `photo_data` column
- Sets `photo_path` to `NULL`
- Cleans up old files if they exist

**All new employees will automatically have compressed blob photos!** ✅

---

## 💾 Database Schema

```sql
employees table:
- photo_path (VARCHAR) - Set to NULL (not used)
- photo_data (LONGBLOB) - Compressed image blob
- photo_content_type (VARCHAR) - Always 'image/jpeg'
```

---

## 🔧 How It Works

### Upload Process:
1. User uploads photo (any size, JPG/PNG/GIF)
2. System compresses:
   - Converts to JPEG
   - Quality: 70%
   - Max width: 800px
   - Result: ~50-150 KB
3. Stores blob in `photo_data` column
4. Sets `photo_path` to `NULL`
5. Deletes any old files (cleanup)

### Display Process:
1. Kiosk requests `/api/employee-photos/123`
2. System queries database for `photo_data`
3. Returns blob with cache headers
4. Browser caches for 24 hours

---

## 📊 Storage Savings

| Employees | Old (Files) | New (Blobs) | Savings |
|-----------|-------------|-------------|---------|
| 100       | 200-500 MB  | 5-15 MB     | 95-97%  |
| 1,000     | 2-5 GB      | 50-150 MB   | 95-97%  |
| 10,000    | 20-50 GB    | 500 MB-1.5 GB | 95-97% |

---

## ⚙️ Configuration

**In `EmployeePhotoController.php`:**
```php
private const COMPRESSED_QUALITY = 70; // JPEG quality (0-100)
private const MAX_WIDTH = 800;         // Max width in pixels
```

**Adjust based on needs:**
- **Smaller files:** Lower quality (60-65) or width (600-700px)
- **Better quality:** Higher quality (75-80) or width (1000px)

---

## 🛠️ Setup & Migration

### 1. Database Already Ready ✅
Your `employees` table already has the required columns!

### 2. Migrate Existing Photos (Optional)

If you have existing photo files, convert them to blobs:

```bash
php artisan photos:compress
```

**Options:**
```bash
# Compress only photos without blobs
php artisan photos:compress

# Force recompress all photos
php artisan photos:compress --force
```

This command will:
- Find all existing photo files
- Compress them to blobs
- Store in database
- Show progress and stats

### 3. Test the System

Open in browser:
```
http://localhost/test-employee-photos.html
```

---

## 🧪 Testing

### Test with cURL:
```bash
# Get photo
curl http://localhost/api/employee-photos/1

# Get photo headers (check size)
curl -I http://localhost/api/employee-photos/1

# Upload photo (requires token)
curl -X POST http://localhost/api/employee-photos/1/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photo=@photo.jpg"
```

### Test with Browser:
```javascript
// Check photo size
fetch('/api/employee-photos/1', { method: 'HEAD' })
  .then(r => {
    console.log('Size:', r.headers.get('content-length'), 'bytes');
    console.log('Type:', r.headers.get('content-type'));
  });
```

---

## 📝 Code Files

### Required:
- `app/Http/Controllers/Api/EmployeePhotoController.php` - Main controller
- `routes/api.php` - API routes
- `app/Console/Commands/CompressEmployeePhotos.php` - Migration command

### Test Files:
- `public/test-employee-photos.html` - Visual test interface

---

## ✅ Benefits

✅ **95-97% less storage** compared to files  
✅ **No file I/O** - database queries only  
✅ **Fast loading** - small compressed images  
✅ **Simple architecture** - one storage location  
✅ **Auto cleanup** - removes old files on upload  
✅ **Cache friendly** - 24-hour cache headers  

---

## 🔐 Security

- Upload requires authentication (`auth:sanctum`)
- Public endpoint is rate-limited (300/min)
- Automatic compression prevents huge uploads
- No direct file system access needed

---

## 🆘 Troubleshooting

### Photo not loading?
```bash
php artisan tinker
>>> $employee = App\Models\Employee::find(1);
>>> $employee->photo_data ? 'Has blob' : 'No blob';
>>> exit
```

### Convert file to blob:
```bash
php artisan photos:compress
```

### Check photo size:
```bash
curl -I http://localhost/api/employee-photos/1 | grep content-length
```

---

## 📦 Dependencies

- **Laravel Framework:** 9.x
- **Intervention Image:** 3.x ✅ (already installed)
- **PHP GD Extension:** Required

All dependencies are already installed!

---

**Questions?** Check logs: `storage/logs/laravel.log`
