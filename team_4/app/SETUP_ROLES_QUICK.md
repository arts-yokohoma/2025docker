# 🚀 Quick Setup Instructions - Role-Based Access

## What Changed

✅ **Updated Files:**
1. `admin/auth.php` - Now queries user role from database
2. `admin/dashboard.php` - Shows different tabs based on user role

## Database Update Required

Run this SQL command on your PostgreSQL database:

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));
UPDATE users SET role = 'supervisor' WHERE username = 'admin';
```

### Option 1: Using psql Command Line
```bash
psql -U team_4 -d team_4_db -c "ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));"
psql -U team_4 -d team_4_db -c "UPDATE users SET role = 'supervisor' WHERE username = 'admin';"
```

### Option 2: Using pgAdmin GUI
1. Open pgAdmin
2. Select your database
3. Go to Query Tool
4. Paste the SQL and execute

### Option 3: Create Test Users

After updating the schema, create a staff user:

```sql
-- Generate hash using PHP first: password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (username, password, role) 
VALUES ('staff_user', '$2y$12$YOUR_BCRYPT_HASH_HERE', 'staff');
```

To generate the bcrypt hash, create a test file:
```php
<?php
echo password_hash('password123', PASSWORD_BCRYPT);
?>
```

## How to Use

### Login as Supervisor (Admin)
- Username: `admin`
- Password: `admin123`
- See: Shift Management + Orders Management tabs

### Login as Staff
- Username: `staff_user` (or any staff user)
- See: Orders Management tab ONLY

## What's Hidden for Staff

When a staff member logs in:
- ❌ **Shift Management tab is hidden**
- ✅ **Orders Management tab is visible**
- ✅ Can view and manage orders
- ❌ Cannot change staff schedules

## What's Visible for Supervisor

When supervisor logs in:
- ✅ **Shift Management tab visible**
- ✅ **Orders Management tab visible**
- ✅ Can manage both shifts and orders
- ✅ User badge shows "👨‍💼 Supervisor"

## Verify It Works

After updating the database:

1. Logout and clear browser cache (Ctrl+Shift+R)
2. Login with admin account
3. Should see both tabs + green supervisor badge
4. Create a staff account and login
5. Should see only orders tab + pink staff badge

## Files Reference

- **Auth updated**: `/admin/auth.php` (queries role on login)
- **Dashboard updated**: `/admin/dashboard.php` (shows tabs based on role)
- **Migration file**: `/db/add_role_column.sql`
- **Full guide**: `ROLE_BASED_ACCESS_GUIDE.md`

---

**Next Steps:**
1. ✅ Run the SQL ALTER command
2. ✅ Test login with admin account
3. ✅ Create staff user accounts
4. ✅ Test staff login

Done! 🎉
