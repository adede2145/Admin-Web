# DTR HEAD Officer Implementation Summary

## Overview

Successfully implemented HEAD officer name and office input functionality for DTR reports. Each employee can have a different HEAD officer assigned, which appears in the signature section of all export formats (PDF, DOCX, CSV, HTML).

## What Was Implemented

### 1. Database
- **Migration**: Created `dtr_head_officer_overrides` table with:
  - `override_id` (primary key)
  - `report_id` (foreign key to dtr_reports)
  - `employee_id` (foreign key to employees)
  - `head_officer_name` (required, varchar 255)
  - `head_officer_office` (optional, varchar 255)
  - Unique constraint on `report_id` + `employee_id`
  - Timestamps

### 2. Model
- **DTRHeadOfficerOverride** model created with:
  - Relationships to DTRReport and Employee
  - Fillable fields for head officer data

### 3. Routes
- `POST /dtr-head-officer` - Store/update head officer information
- `DELETE /dtr-head-officer` - Remove head officer information

### 4. Controller Methods
- **DTROverrideController**:
  - `storeHeadOfficer()` - Save/update head officer name and office
  - `destroyHeadOfficer()` - Delete head officer override
  - Both include RBAC checks (department restrictions)

- **AttendanceController**:
  - Updated `dtrDetails()` to load head officer overrides
  - Updated `downloadDTR()` to load and pass head officer overrides
  - All export methods updated to include head officer information

### 5. Export Formats Updated
All export formats now include HEAD officer information in the signature section:

- **PDF** (via DOCX template): Uses template variables `${head_officer_name}` and `${head_officer_office}`
- **DOCX**: Template variables are set automatically
- **CSV**: Head officer name and office appear below "VERIFIED as to the prescribed office hours"
- **HTML/PDF**: Head officer information displayed in signature section

### 6. UI Implementation
- **Details Page** (`resources/views/dtr/details.blade.php`):
  - Display current head officer information below each employee's attendance table
  - "Set/Edit" button to open modal
  - Modal form with:
    - Head Officer Name field (required)
    - Office/Title field (optional)
    - Save and Remove buttons

## Template Variables for DOCX Template

**IMPORTANT**: You need to add these placeholder variables in your DOCX template (`storage/app/templates/dtr_template.docx`) where you want the HEAD officer information to appear (typically at the bottom, below "VERIFIED as to the prescribed office hours"):

- `${head_officer_name}` - Full name of the HEAD officer (e.g., "DIXIE JEAN V. CARESOSA, MPRM, LPT")
- `${head_officer_office}` - Office/Title of the HEAD officer (e.g., "Head, Budget Office")

### Example Placement in Template:

```
VERIFIED as to the prescribed office hours.

________________________________________
${head_officer_name}
${head_officer_office}
```

## How to Use

1. **Set Head Officer**:
   - Go to DTR Details page for any report
   - Scroll to an employee's detailed attendance section
   - Click "Set" or "Edit" button in the HEAD Officer section
   - Enter the head officer name (required) and office/title (optional)
   - Click "Save"

2. **Remove Head Officer**:
   - Click "Edit" in the HEAD Officer section
   - Click "Remove" button
   - Confirm deletion

3. **Export**:
   - Head officer information will automatically appear in all export formats (PDF, DOCX, CSV, HTML)
   - Each employee can have a different head officer

## Database Migration

Run the migration to create the table:
```bash
php artisan migrate
```

## Files Modified

1. `database/migrations/2025_12_05_012715_create_dtr_head_officer_overrides_table.php` - New migration
2. `app/Models/DTRHeadOfficerOverride.php` - New model
3. `app/Http/Controllers/DTROverrideController.php` - Added head officer methods
4. `app/Http/Controllers/AttendanceController.php` - Updated all export methods
5. `routes/web.php` - Added routes
6. `resources/views/dtr/details.blade.php` - Added UI for head officer input

## Next Steps

1. **Add Template Variables**: Edit your DOCX template (`storage/app/templates/dtr_template.docx`) and add the placeholder variables `${head_officer_name}` and `${head_officer_office}` in the signature section.

2. **Run Migration**: Execute the migration to create the database table.

3. **Test**: 
   - Create a DTR report
   - Go to details page
   - Set head officer information for employees
   - Export in different formats to verify head officer information appears correctly

## Notes

- Head officer information is per employee per report (one head officer per employee per DTR report)
- RBAC restrictions apply - admins can only edit head officers for employees in their department (unless super admin)
- If no head officer is set, exports will show "In Charge" as placeholder
- Head officer information appears in all export formats consistently

