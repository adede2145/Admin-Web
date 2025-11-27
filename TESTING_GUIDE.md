# Multi-Employee DTR Export - Testing Guide

## Quick Test Checklist

### ✅ Pre-Testing Verification
Before testing, verify the following:

1. **Template File Exists**
   - Check: `storage/app/templates/dtr_template.docx`
   - This file should already exist in your project

2. **Storage Directory Permissions**
   - Ensure `storage/app/` is writable
   - Check: `chmod -R 775 storage/` (Linux/Mac) or verify permissions on Windows

3. **Route Configuration**
   - Route already configured: `/dtr/{id}/download/{format?}`
   - No changes needed

---

## Test Scenarios

### 🔵 Scenario 1: Single Employee (Backward Compatibility)

**Purpose**: Verify existing functionality still works

**Steps**:
1. Login to admin panel
2. Navigate to **Reports** page
3. Click **"Generate DTR Report"** button
4. Select **ONE employee** from the list
5. Choose date range (e.g., current month)
6. Click **"Generate Report"**
7. On the report details page, click **Download** dropdown
8. Test each format:
   - Click **PDF** → Should download single PDF file
   - Go back, click **DOCX** → Should download single DOCX file
   - Go back, click **CSV** → Should download single CSV file

**Expected Result**: ✅ Single files download directly (NO ZIP)

---

### 🟢 Scenario 2: Two Employees (Basic Multi-Export)

**Purpose**: Test basic multi-employee export

**Steps**:
1. Navigate to **Reports** → **"Generate DTR Report"**
2. Select **TWO employees** from the list
3. Choose date range (e.g., current month)
4. Click **"Generate Report"**
5. On the report details page, click **Download** dropdown
6. Test each format:
   - Click **PDF** → Should download ZIP file
   - Extract ZIP → Should contain 2 PDF files (one per employee)
   - Repeat for DOCX and CSV

**Expected Result**: ✅ ZIP file contains 2 individual DTR files

**Filename Pattern**:
```
DTR_Report_123_2024-11-01_to_2024-11-30.zip
  ├── DTR_Juan_Dela_Cruz_2024-11-01_to_2024-11-30.pdf
  └── DTR_Maria_Santos_2024-11-01_to_2024-11-30.pdf
```

---

### 🟡 Scenario 3: Select All Employees

**Purpose**: Test bulk export functionality

**Steps**:
1. Navigate to **Reports** → **"Generate DTR Report"**
2. Click **"Select All Employees"** checkbox
3. Verify all employees are checked
4. Choose date range (e.g., current month)
5. Click **"Generate Report"**
6. Wait for report generation (may take longer with many employees)
7. Click **Download** → **DOCX** (test one format first)
8. Verify ZIP downloads
9. Extract and verify:
   - Number of files matches number of employees
   - Each file opens correctly
   - Each file contains correct employee data

**Expected Result**: ✅ ZIP file contains DTR for ALL employees

---

### 🔴 Scenario 4: Error Handling

**Purpose**: Verify system handles errors gracefully

**Test Cases**:

#### A. Missing Template (Optional - Destructive Test)
1. Temporarily rename `storage/app/templates/dtr_template.docx`
2. Try to generate DOCX report
3. Should see error message: "Template file not found"
4. Restore template file

#### B. Large Employee Count
1. Select 20+ employees (if available)
2. Generate report
3. Download DOCX
4. Verify all employees are included
5. Check `storage/logs/laravel.log` for any errors

---

## Format-Specific Checks

### 📄 DOCX Format
- [ ] File opens in Microsoft Word
- [ ] File opens in LibreOffice Writer
- [ ] Employee name is correct
- [ ] Date range is correct
- [ ] Attendance data is accurate
- [ ] Weekend rows are merged/highlighted
- [ ] Leave days are properly marked

### 📕 PDF Format
- [ ] File opens in Adobe Reader
- [ ] File opens in browser
- [ ] Layout is correct (2-column format)
- [ ] All data is visible
- [ ] No text overflow issues

### 📊 CSV Format
- [ ] File opens in Excel/Google Sheets
- [ ] Headers are correct
- [ ] Data is properly formatted
- [ ] Commas are escaped correctly
- [ ] Special characters display properly

---

## Common Issues & Solutions

### Issue: "Template file not found"
**Solution**: 
```bash
# Verify template exists
ls storage/app/templates/dtr_template.docx

# If missing, restore from backup or repo
```

### Issue: "Could not create ZIP file"
**Solution**:
```bash
# Check storage permissions
chmod -R 775 storage/app/

# Check PHP ZipArchive extension
php -m | grep zip
```

### Issue: ZIP downloads but is corrupt
**Solution**:
- Check storage disk space: `df -h`
- Check PHP memory limit in `php.ini`
- Review `storage/logs/laravel.log` for errors

### Issue: One employee missing from ZIP
**Solution**:
- Check `storage/logs/laravel.log`
- That employee's DTR may have failed (error is logged)
- Other employees still export successfully

---

## Performance Benchmarks

Expected generation times (approximate):

| Employee Count | DOCX | PDF | CSV |
|---------------|------|-----|-----|
| 1 | < 1s | < 1s | < 1s |
| 5 | 3-5s | 2-4s | 1-2s |
| 10 | 6-10s | 4-8s | 2-4s |
| 20 | 12-20s | 8-16s | 4-8s |
| 50+ | 30-60s | 20-40s | 10-20s |

*Times vary based on server performance and date range*

---

## Post-Testing Cleanup

After testing, check:

1. **Temporary Files Cleaned Up**
   ```bash
   ls storage/app/temp_dtr_*
   # Should be empty or very few files
   ```

2. **Log Files**
   ```bash
   tail -n 50 storage/logs/laravel.log
   # Check for any errors or warnings
   ```

3. **Storage Space**
   ```bash
   du -sh storage/app/
   # Verify no excessive disk usage
   ```

---

## Success Criteria

The implementation is successful if:

- ✅ Single employee exports work (no regression)
- ✅ Multiple employee exports generate ZIP files
- ✅ All three formats (DOCX, PDF, CSV) work correctly
- ✅ "Select All" functionality works
- ✅ Files contain accurate data
- ✅ Filenames are properly formatted
- ✅ No critical errors in logs
- ✅ Temporary files are cleaned up

---

## Reporting Issues

If you encounter issues:

1. Check `storage/logs/laravel.log` for error details
2. Note the exact steps to reproduce
3. Document the error message
4. Check the employee count and date range used
5. Verify PHP version and extensions installed

Happy Testing! 🎉

