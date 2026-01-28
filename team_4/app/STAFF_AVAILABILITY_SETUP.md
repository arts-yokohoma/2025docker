# Staff Availability & Order Blocking System

## Overview
This system automatically prevents customers from placing orders when no staff members are scheduled. This ensures the pizza shop doesn't accept orders they can't fulfill.

## How It Works

### 1. **Database Schema**
A new `staff_shifts` table has been added to track staff availability:

```sql
staff_shifts (
  - shift_date: Date for the shift
  - shift_type: 'morning' (8AM-4PM) or 'evening' (4PM-12AM)
  - staff_count: Number of staff members scheduled
  - max_orders_per_hour: Maximum orders per staff member per hour (default: 4)
  - current_orders: Current number of active orders
  - is_active: Whether this shift is active
)
```

### 2. **Capacity Check Function**
Added `checkOrderCapacity()` function in `db/db.php` that:
- Determines current time and shift (morning/evening)
- Checks if a shift is scheduled for today
- Verifies staff count is > 0
- Validates current orders haven't exceeded capacity
- Returns boolean indicating if orders can be accepted

### 3. **Order Page Blocking**
Updated `order.php` to:
- Call `checkOrderCapacity()` on page load
- Display error banner if no staff available
- Hide order form and show "Back to Home" link

## Setup Instructions

### Step 1: Initialize Database
Run the init.sql script to create the staff_shifts table:
```bash
psql -U team_4 -h localhost -d team_4_db -f db/init.sql
```

### Step 2: Add Staff Shifts via Admin Dashboard
1. Log in to admin dashboard (`/admin/login.php`)
2. Go to "Shift Management" tab
3. Select a date and allocate staff for morning/evening shifts
4. Click "Save Shift Schedule"

### Step 3: Test the Feature
**When NO staff is scheduled:**
- Customer visits `/order.php`
- Gets error: "Sorry! We are currently closed. No staff scheduled for orders."
- Cannot place order

**When staff ARE scheduled:**
- Customer can browse and place orders normally
- Orders submitted successfully

## Admin Dashboard Controls

In the Shift Management tab:
- **Morning Shift**: 8 AM - 4 PM
- **Evening Shift**: 4 PM - 12 AM
- **Staff Count**: Number of delivery staff available
- **Capacity Calculation**: Staff count × 2 orders per hour

## Error Messages Shown to Customers

1. **No shift scheduled:**
   - "We are currently closed. No staff scheduled for orders."

2. **Staff count is zero:**
   - "We are currently closed. No delivery staff available."

3. **At maximum capacity:**
   - "We are at maximum capacity. Please try again later."

4. **Database error:**
   - "Unable to verify capacity. Please try again."

## File Changes Made

1. **db/init.sql** - Added `staff_shifts` table
2. **db/db.php** - Added `checkOrderCapacity()` function
3. **order.php** - Added capacity check and error banner display

## Testing Tips

1. **Set staff to 0** to test error blocking
2. **Clear shift schedule** to test "no shift" scenario
3. **Monitor shifts** from admin dashboard for upcoming orders
4. **Check logs** in error_log for debugging

## Future Enhancements

- Add order tracking in `current_orders` field
- Implement automatic queue management
- Add SMS/Email notifications when shifts are added
- Create shift templates for recurring schedules
