# DTR Modal Updates - Attendance & Reports Views

## Summary

Successfully updated the DTR generation modals in both attendance and reports views to use the new employment type filtering system with monthly-only selection.

## Files Updated

### 1. `resources/views/attendance/index.blade.php`

**Changes Made:**

#### First Modal Instance (lines ~983-1080)
- ✅ **Removed**: Report type dropdown (weekly/monthly/custom options)
- ✅ **Removed**: Department/Office filter dropdown
- ✅ **Added**: Employment type filter with conditional display logic
  - Shows dropdown when admin has multiple employment types
  - Shows read-only text when admin has single employment type
  - Properly implements RBAC based on `employment_type_access`
- ✅ **Replaced**: Start date and end date inputs with single month picker
  - Uses Flatpickr with monthSelect plugin
  - Defaults to current month
  - Auto-calculates first and last day of selected month
  - Stores values in hidden `start_date_1` and `end_date_1` fields
- ✅ **Added**: Hidden `report_type` field (always "monthly")

#### Second Modal Instance (lines ~1095-1210)
- ✅ **Removed**: Report type dropdown
- ✅ **Removed**: Department filter (Super Admin) and office display (Non-Admin)
- ✅ **Added**: Employment type filter with same conditional logic
- ✅ **Replaced**: Date inputs with month picker (`month_picker_2`)
  - Hidden fields: `start_date_2` and `end_date_2`
- ✅ **Added**: Hidden `report_type` field (always "monthly")

#### JavaScript Updates
- ✅ **Added**: Flatpickr library includes (CSS and JS)
- ✅ **Added**: Month picker initialization for both modals
  - `initMonthPicker()` helper function
  - Automatic date calculation (first and last day of month)
  - Default to current month
  - Max date set to current month (prevents future selection)
- ✅ **Kept**: Legacy date adjustment scripts for backward compatibility

### 2. `resources/views/reports/index.blade.php`

**Changes Made:**

#### DTR Modal (lines ~291-390)
- ✅ **Removed**: Report type dropdown
- ✅ **Removed**: Department/Office filter
- ✅ **Added**: Employment type filter
  - Conditional display based on admin access
  - Same RBAC logic as attendance view
- ✅ **Replaced**: Start/end date inputs with month picker
  - Field IDs: `month_picker_reports`, `start_date_reports`, `end_date_reports`
  - Auto-calculation of month boundaries
- ✅ **Added**: Hidden `report_type` field (always "monthly")

#### JavaScript Updates
- ✅ **Added**: Flatpickr library includes
- ✅ **Added**: Month picker initialization
  - Integrated with existing DOMContentLoaded handlers
  - Proper date formatting and hidden field updates
- ✅ **Kept**: Legacy scripts for compatibility

## Employment Type Mapping

Both files use consistent employment type mapping:

```php
'full_time' => 'Full-Time'
'part_time' => 'Part-Time'
'cos' => 'COS'
'admin' => 'Admin'
'faculty with designation' => 'Faculty'
```

## RBAC Implementation

### Super Admin
- Sees all employment types in dropdown
- Can generate DTR for any employment type

### HR Admin (department = 'hr' or 'office hr')
- Sees employment types from `employment_type_access` array
- Shows dropdown if multiple types, text label if single type

### Non-HR Admin
- Sees employment types from `employment_type_access` array
- Shows dropdown if multiple types, text label if single type
- Backend also filters by department_id

## Month Picker Behavior

- **Library**: Flatpickr with monthSelect plugin
- **Default Value**: Current month (e.g., "December 2025")
- **Max Date**: Current month (cannot select future months)
- **Auto-Calculation**: 
  - First day: YYYY-MM-01
  - Last day: YYYY-MM-[28-31] (handles leap years)
- **Format**: "Month YYYY" display, "YYYY-MM-DD" for hidden fields

## Unique Field IDs

To avoid conflicts with multiple modals on the same page:

### Attendance View
- **Modal 1**: `month_picker_1`, `start_date_1`, `end_date_1`
- **Modal 2**: `month_picker_2`, `start_date_2`, `end_date_2`

### Reports View
- **Modal**: `month_picker_reports`, `start_date_reports`, `end_date_reports`

## Backward Compatibility

- ✅ Kept legacy JavaScript for older functionality
- ✅ Hidden `report_type` field always set to "monthly"
- ✅ Employee selection and search functionality preserved
- ✅ No breaking changes to form submission

## Testing Checklist

### UI Testing
- [ ] Month picker displays correctly in both views
- [ ] Employment type filter shows/hides based on admin access
- [ ] Text label displays for single employment type admins
- [ ] Month picker defaults to current month

### Functional Testing
- [ ] Month selection updates hidden start_date and end_date fields
- [ ] Form submits with correct employment_type value
- [ ] Backend receives monthly report_type
- [ ] Employee selection works correctly

### RBAC Testing
- [ ] Super Admin: sees all employment types
- [ ] HR Admin: sees accessible employment types only
- [ ] Non-HR Admin: sees accessible employment types only
- [ ] Unauthorized access properly blocked

### Cross-Browser Testing
- [ ] Chrome/Edge: Flatpickr renders correctly
- [ ] Firefox: Month picker works
- [ ] Safari: No JavaScript errors
- [ ] Mobile: Touch interactions work

## Known Dependencies

1. **Flatpickr Library**:
   - Main: `https://cdn.jsdelivr.net/npm/flatpickr`
   - Plugin: `flatpickr/dist/plugins/monthSelect/index.js`
   - CSS: `flatpickr/dist/flatpickr.min.css`
   - Plugin CSS: `flatpickr/dist/plugins/monthSelect/style.css`

2. **Backend Validation**:
   - `AttendanceController@generateDTR()` expects:
     - `employment_type` (required)
     - `report_type` (must be "monthly")
     - `start_date` and `end_date` (auto-calculated from month)

3. **Admin Model**:
   - `employment_type_access` attribute (array)
   - `isSuperAdmin()` method
   - `canManageEmployee()` method

## File Locations

- **Attendance View**: `resources/views/attendance/index.blade.php`
- **Reports View**: `resources/views/reports/index.blade.php`
- **Backend Controller**: `app/Http/Controllers/AttendanceController.php`
- **Service**: `app/Services/DTRService.php`

## Implementation Date

December 4, 2025

---

**Status**: ✅ **Complete** - Both modals updated and ready for testing

