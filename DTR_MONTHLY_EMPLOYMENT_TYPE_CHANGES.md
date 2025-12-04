# DTR Generation - Monthly Employment Type Implementation

## Summary

Successfully implemented the DTR generation system modifications to use monthly-only reports with employment type filtering instead of department-based filtering.

## Changes Made

### 1. Frontend - Attendance Index View
**File**: `resources/views/admin/attendance/index.blade.php`

- ✅ Removed weekly/custom report type options (only monthly supported now)
- ✅ Replaced department filter with employment type filter
  - Shows dropdown when admin has access to multiple employment types
  - Shows read-only text when admin has access to only one employment type
  - Super admins see all employment types
  - HR/Non-HR admins see their configured accessible types
- ✅ Added month picker using Flatpickr library
  - Defaults to current month
  - Max date set to current month (prevents future selection)
  - Automatically calculates first and last day of selected month
- ✅ Hidden fields for start_date and end_date (auto-populated from month selection)
- ✅ Hidden field for report_type (always "monthly")

### 2. Backend - AttendanceController
**File**: `app/Http/Controllers/AttendanceController.php`

- ✅ Updated `generateDTR()` validation rules:
  - Changed `department_id` to `employment_type` (required)
  - Validates employment_type against enum values
  - Report type validation changed to only accept "monthly"
- ✅ Implemented RBAC checks for employment type access:
  - Super admins can generate for any employment type
  - HR admins restricted to their configured employment types
  - Non-HR admins restricted to their employment types
- ✅ Updated export filename generation in `downloadDTR()`:
  - Single employee: `DTR_[EmployeeName]_YYYY-MM.ext`
  - Multiple employees: `DTR_Report_[EmploymentType]_YYYY-MM.zip`
- ✅ Updated multiple employee download methods:
  - `downloadMultipleEmployeeDOCXs()` - uses YYYY-MM format
  - `downloadMultipleEmployeePDFs()` - uses YYYY-MM format
  - `downloadMultipleEmployeeCSVs()` - uses YYYY-MM format

### 3. Backend - DTRService
**File**: `app/Services/DTRService.php`

- ✅ Updated `generateDTRReport()` method:
  - Accepts `employment_type` parameter instead of `department_id`
  - Filters employees by employment type
  - For non-HR admins, also applies department filter
  - For HR admins, only filters by employment type (department-agnostic)
  - Uses `canManageEmployee()` method for final validation
- ✅ Updated `generateReportTitle()` method:
  - Shows employment type label instead of department name
  - Date period format changed to "Month YYYY" (e.g., "December 2025")
  - Example: "Full-Time Employees Monthly Report - December 2025"
- ✅ Stores employment_type in DTRReport record

### 4. Database Migration
**File**: `database/migrations/2025_12_04_193113_add_employment_type_to_dtr_reports_table.php`

- ✅ Added `employment_type` column to `dtr_reports` table
  - ENUM type matching employees table
  - Positioned after `department_id`
  - Nullable (for backward compatibility with existing reports)
- ✅ Migration executed successfully

### 5. Model Updates
**File**: `app/Models/DTRReport.php`

- ✅ Added `employment_type` to fillable fields

## Employment Type Mapping

The system uses the following employment type labels:
- `full_time` → "Full-Time"
- `part_time` → "Part-Time"
- `cos` → "COS"
- `admin` → "Admin"
- `faculty with designation` → "Faculty"

## RBAC Logic

### Super Admin
- Can generate DTR for any employment type
- Sees all employment types in dropdown

### HR Admin (department_name = 'hr' or 'office hr')
- Can generate DTR for employment types in their `employment_type_access` array
- **Does NOT filter by department** - can see all employees of accessible types
- Shows dropdown if multiple types configured, text label if single type

### Non-HR Admin
- Can generate DTR for employment types in their `employment_type_access` array
- **Also filters by their department** - only sees employees in their department with accessible types
- Shows dropdown if multiple types configured, text label if single type

## File Naming Convention

### Single Employee Export
- Format: `DTR_[EmployeeName]_YYYY-MM.[ext]`
- Example: `DTR_John_Doe_2025-12.docx`

### Multiple Employee Export
- Format: `DTR_Report_[EmploymentType]_YYYY-MM.zip`
- Example: `DTR_Report_Full-Time_2025-12.zip`
- Contains individual files: `DTR_[EmployeeName]_YYYY-MM.[ext]`

## Month Picker Behavior

- **Library**: Flatpickr with monthSelect plugin
- **Default**: Current month (December 2025 as of implementation)
- **Max Date**: Current month (cannot select future months)
- **Format**: "Month YYYY" (e.g., "December 2025")
- **Auto-calculation**: Automatically sets hidden start_date (YYYY-MM-01) and end_date (YYYY-MM-[28-31])

## Backward Compatibility

- `department_id` column retained in dtr_reports table for historical reports
- Old reports (weekly/custom) remain viewable
- New `employment_type` column is nullable

## Template Integration

**Status**: ⏳ **Awaiting user's new DTR template**

The DOCX export functionality is ready to integrate the user's custom template once provided. Current template path: `storage/app/templates/dtr_template.docx`

## Testing Recommendations

### UI Testing
1. ✅ Verify month picker displays with correct default (current month)
2. ✅ Test employment type filter visibility (dropdown vs text label)
3. ✅ Confirm proper employment type options based on admin role

### Functional Testing
1. Generate DTR for single employee
2. Generate DTR for multiple employees (verify ZIP creation)
3. Test all export formats (PDF, DOCX, CSV)
4. Verify filenames match new YYYY-MM format

### RBAC Testing
1. Super Admin: can generate for any employment type
2. HR Admin: can generate for accessible employment types (all departments)
3. Non-HR Admin: can generate for accessible employment types (own department only)
4. Verify unauthorized access is blocked with error messages

### Edge Cases
1. February (28/29 days)
2. Months with 30 days
3. Months with 31 days
4. Employees with no attendance data
5. Current incomplete month

## Files Modified

1. `resources/views/admin/attendance/index.blade.php` - Frontend UI
2. `app/Http/Controllers/AttendanceController.php` - Backend validation and exports
3. `app/Services/DTRService.php` - Business logic
4. `app/Models/DTRReport.php` - Model fillable fields
5. `database/migrations/2025_12_04_193113_add_employment_type_to_dtr_reports_table.php` - Database schema

## Implementation Date

December 4, 2025

---

**Status**: ✅ **Implementation Complete** (awaiting template integration)

