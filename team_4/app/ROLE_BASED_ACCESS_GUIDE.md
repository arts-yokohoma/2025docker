# 👥 Role-Based Access Control Setup Guide

## Overview
Your admin system now supports two user roles:
- **👨‍💼 Supervisor**: Can access both Shift Management and Orders Management
- **👷 Staff**: Can only access Orders Management

## Step 1: Update Database Schema

### Add Role Column to Users Table

Run this SQL command in your PostgreSQL database:

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));
```

Or use the migration file:
```bash
psql -U team_4 -d team_4_db -f db/add_role_column.sql
```

### Set Admin User as Supervisor

```sql
UPDATE users SET role = 'supervisor' WHERE username = 'admin';
```

## Step 2: Create Users with Different Roles

### Create a Supervisor User

```sql
INSERT INTO users (username, password, role) 
VALUES ('supervisor1', '$2y$12$...bcrypt_hash...', 'supervisor');
```

**Generate bcrypt hash in PHP:**
```php
<?php
$password = 'your_password_here';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
?>
```

### Create a Staff User

```sql
INSERT INTO users (username, password, role) 
VALUES ('staff1', '$2y$12$...bcrypt_hash...', 'staff');
```

## Step 3: Test the System

### Login as Supervisor
- Username: `admin` (or your supervisor user)
- Password: `admin123` (or your password)
- **Result**: Dashboard shows both "📋 Shift Management" and "📦 Orders Management" tabs

### Login as Staff
- Username: `staff1` (or your staff user)
- Password: (your password)
- **Result**: Dashboard shows only "📦 Orders Management" tab

## Complete SQL Script

Run this to set up everything at once:

```sql
-- 1. Add role column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));

-- 2. Update existing admin to supervisor
UPDATE users SET role = 'supervisor' WHERE username = 'admin';

-- 3. Create a sample staff user
-- First, generate the bcrypt hash in PHP and replace the placeholder
INSERT INTO users (username, password, role) 
VALUES ('staff_demo', '$2y$12$...hash_here...', 'staff')
ON CONFLICT DO NOTHING;

-- 4. Verify setup
SELECT id, username, role FROM users;
```

## How It Works

### Authentication Flow (auth.php)
1. User submits login form
2. System queries user with role: `SELECT id, username, password, role FROM users WHERE username = ?`
3. If password matches, session stores: `$_SESSION['role']` = user's role
4. Redirects to dashboard

### Dashboard Logic (dashboard.php)
1. Retrieves role from session: `$userRole = $_SESSION['role'] ?? 'staff'`
2. Checks if supervisor: `$isSupervisor = ($userRole === 'supervisor')`
3. **For Supervisor:**
   - Shows "📋 Shift Management" tab
   - Shows "📦 Orders Management" tab
   - Default active tab: Shift Management
4. **For Staff:**
   - Hides "📋 Shift Management" tab (with PHP `style="display: none;"`)
   - Shows "📦 Orders Management" tab
   - Default active tab: Orders Management
5. Displays user info in header with role badge

## User Interface Changes

### Header Shows:
- 👤 Username
- Role badge with color coding:
  - 👨‍💼 **Supervisor** (green badge)
  - 👷 **Staff** (pink badge)
- Logout link

### Tabs:
- **Supervisor sees:**
  ```
  [📋 Shift Management] [📦 Orders Management]
  ```
  
- **Staff sees:**
  ```
  [📦 Orders Management]
  ```

## Database Schema

### Updated Users Table:
```
users
├── id (SERIAL PRIMARY KEY)
├── username (TEXT UNIQUE NOT NULL)
├── password (TEXT NOT NULL)
└── role (VARCHAR(50) DEFAULT 'staff')
    ├── 'supervisor' → Full access
    └── 'staff' → Orders only
```

## File Changes

### Modified Files:
1. **admin/auth.php** - Now queries and stores role in session
2. **admin/dashboard.php** - Conditionally shows tabs based on role

### New Files:
- **db/add_role_column.sql** - Migration to add role column

## Quick Reference

| User Type | Can See | Session Role | Badge Color |
|-----------|---------|--------------|------------|
| Supervisor | Shift + Orders | `supervisor` | 🟢 Green |
| Staff | Orders Only | `staff` | 🔴 Pink |

## Troubleshooting

### Role column already exists
If you get an error that the column already exists, just run:
```sql
UPDATE users SET role = 'supervisor' WHERE username = 'admin';
```

### Users not showing correct tabs
1. Clear browser cache
2. Logout and login again
3. Check `$_SESSION['role']` is being set in auth.php
4. Verify role in database: `SELECT username, role FROM users;`

### "Object of class stdClass could not be converted to string"
Make sure session data is set before using it:
```php
$userRole = $_SESSION['role'] ?? 'staff'; // Default to 'staff'
```

## Future Enhancements

You can extend this system with more roles:
- `admin` → Full system access + user management
- `manager` → View reports + manage orders
- Custom permissions per role

Just add more role values to the CHECK constraint:
```sql
ALTER TABLE users 
DROP CONSTRAINT users_role_check,
ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'supervisor', 'manager', 'staff'));
```

---

**Status**: ✅ Role-based access control implemented
**Security**: Sessions are used to store and verify roles on each page load
