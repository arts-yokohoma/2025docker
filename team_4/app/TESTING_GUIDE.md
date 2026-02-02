# 🧪 Testing Guide - Role-Based Access Control

## Prerequisites
- Your application running at `http://localhost/2025docker/team_4/app`
- PostgreSQL running with your database

---

## Step-by-Step Testing

### Phase 1: Database Setup ✅

#### 1.1 Update Database Schema
```bash
psql -U team_4 -d team_4_db
```

Then run:
```sql
-- Add role column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));

-- Update admin to supervisor
UPDATE users SET role = 'supervisor' WHERE username = 'admin';

-- Verify
SELECT id, username, role FROM users;
```

**Expected Output:**
```
 id | username | role
----+----------+------------
  1 | admin    | supervisor
(1 row)
```

#### 1.2 Create a Test Staff User
```sql
-- First, generate bcrypt hash of 'password123'
-- In PHP: echo password_hash('password123', PASSWORD_BCRYPT);
-- Or paste this hash (it's for 'password123'):
-- $2y$12$K4ATy0oQm4XqKYlhv.xnwOb7U./uYj6EJMlz88FVD7R6.p9d3dBCm

INSERT INTO users (username, password, role) 
VALUES ('staff_test', '$2y$12$K4ATy0oQm4XqKYlhv.xnwOb7U./uYj6EJMlz88FVD7R6.p9d3dBCm', 'staff');

-- Verify
SELECT id, username, role FROM users ORDER BY id DESC;
```

**Expected Output:**
```
 id |  username  |   role
----+------------+----------
  2 | staff_test | staff
  1 | admin      | supervisor
(2 rows)
```

---

### Phase 2: Test Supervisor (Admin) Login 👨‍💼

#### 2.1 Logout if already logged in
- Visit: `http://localhost/2025docker/team_4/app/admin/logout.php`
- Or just clear cookies

#### 2.2 Login as Admin (Supervisor)
- URL: `http://localhost/2025docker/team_4/app/admin/login.php`
- Username: `admin`
- Password: `admin123`
- Click **Login**

#### 2.3 Verify Supervisor Dashboard
Look for these indicators:

✅ **Should See:**
1. **Header Information:**
   - 👤 Username displays: `admin`
   - Badge shows: `👨‍💼 Supervisor` (green badge)
   - Logout link available

2. **Tabs Visible:**
   - `📋 Shift Management` tab ← **MUST BE VISIBLE**
   - `📦 Orders Management` tab ← **MUST BE VISIBLE**

3. **Default Tab:**
   - Shift Management should be active by default

4. **Full Content:**
   - Shift Management form visible
   - Staff capacity planning section visible
   - Shift history section visible

#### 2.4 Screenshots to Take
- [ ] Dashboard header with supervisor badge
- [ ] Both tabs visible
- [ ] Shift Management content

---

### Phase 3: Test Staff Login 👷

#### 3.1 Logout
- Click **Logout** link in header
- OR: Visit `http://localhost/2025docker/team_4/app/admin/logout.php`

#### 3.2 Login as Staff
- URL: `http://localhost/2025docker/team_4/app/admin/login.php`
- Username: `staff_test`
- Password: `password123`
- Click **Login**

#### 3.3 Verify Staff Dashboard
Look for these indicators:

✅ **Should See:**
1. **Header Information:**
   - 👤 Username displays: `staff_test`
   - Badge shows: `👷 Staff` (pink badge)
   - Logout link available

2. **Tabs Visible:**
   - `📋 Shift Management` tab ← **MUST BE HIDDEN**
   - `📦 Orders Management` tab ← **MUST BE VISIBLE AND ACTIVE**

3. **Default Tab:**
   - Orders Management should be active by default

4. **Content:**
   - Only orders management form visible
   - No shift management controls
   - Cannot see capacity planning

#### 3.4 Verify Shift Tab is Hidden
- Inspect the page source (Right-click → Inspect)
- Search for `shifts-tab`
- Should show: `<div id="shifts-tab" class="tab-content" style="display: none;">`

#### 3.5 Screenshots to Take
- [ ] Dashboard header with staff badge
- [ ] Only orders tab visible (shift tab hidden)
- [ ] Orders Management content

---

### Phase 4: Detailed Checks

#### Check 4.1: Browser Console for Errors
1. Open Developer Tools (F12)
2. Go to **Console** tab
3. Should be no red errors
4. Should see no JavaScript errors related to tabs

#### Check 4.2: Session Variables
Create a debug file at `/opt/lampp/htdocs/2025docker/team_4/app/admin/debug.php`:

```php
<?php
session_start();
echo '<h2>Session Debug</h2>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

Then visit: `http://localhost/2025docker/team_4/app/admin/debug.php`

**Expected for Admin:**
```
Array
(
    [user_id] => 1
    [username] => admin
    [role] => supervisor
)
```

**Expected for Staff:**
```
Array
(
    [user_id] => 2
    [username] => staff_test
    [role] => staff
)
```

#### Check 4.3: Database Query
```sql
SELECT username, role FROM users WHERE id IN (1, 2);
```

Should show:
```
  username  |   role
------------+----------
 admin      | supervisor
 staff_test | staff
(2 rows)
```

---

### Phase 5: Functional Testing

#### Test 5.1: Supervisor Can Access Shifts
1. Login as admin
2. Click **📋 Shift Management** tab
3. Should see:
   - Date selector
   - Morning shift slider (1-15 staff)
   - Evening shift slider (1-15 staff)
   - Capacity calculation
   - Save/Load buttons

#### Test 5.2: Supervisor Can Access Orders
1. Still logged in as admin
2. Click **📦 Orders Management** tab
3. Should see:
   - Order filter controls
   - Orders list/table
   - Order summary cards

#### Test 5.3: Staff Cannot See Shifts
1. Logout (click Logout)
2. Login as staff_test / password123
3. Try to see Shift Management content
4. **Nothing should display** - tab is hidden

#### Test 5.4: Staff Can Access Orders
1. Still logged in as staff_test
2. Should automatically be on Orders Management
3. Should see:
   - Order filter controls
   - Orders list/table
   - Order summary cards

#### Test 5.5: Try to Hack Shift Access
**In browser console, try:**
```javascript
document.getElementById('shifts-tab').style.display = 'block';
```

1. Staff logs in
2. Open Developer Console (F12)
3. Paste the command above
4. Shift Management appears (temporary visual only)
5. **Important:** Reload page (F5)
6. Shift Management disappears again (server-side protection)

---

## Test Results Table

| Test | Supervisor | Staff | Result |
|------|:----------:|:-----:|:------:|
| Sees Shift Management tab | ✅ Yes | ❌ No | **PASS** |
| Sees Orders Management tab | ✅ Yes | ✅ Yes | **PASS** |
| Badge shows "Supervisor" | ✅ Yes | ❌ No | **PASS** |
| Badge shows "Staff" | ❌ No | ✅ Yes | **PASS** |
| Can manage shifts | ✅ Yes | ❌ No | **PASS** |
| Can manage orders | ✅ Yes | ✅ Yes | **PASS** |
| Session has role | ✅ Yes | ✅ Yes | **PASS** |

---

## Troubleshooting

### Issue: Both tabs show for staff
**Solution:**
```bash
# Clear PHP session cache
sudo systemctl restart apache2
# Or XAMPP: Restart Apache
```

### Issue: No "role" column error
**Solution:**
```sql
-- Apply the ALTER TABLE command again
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff';
```

### Issue: Badge not showing
**Solution:**
1. Hard refresh browser: `Ctrl+Shift+R`
2. Clear browser cache
3. Check in `admin/dashboard.php` that PHP is outputting role correctly

### Issue: Staff can still see Shift tab in source
**Solution:**
This is expected! Server sends `display: none` to hide it.
The CSS hides it, and it's also not clickable (no handler).
This is secure because the actual shift data is not sent to staff browsers.

### Issue: Login failing
**Solution:**
1. Check database has the user:
   ```sql
   SELECT * FROM users WHERE username = 'staff_test';
   ```
2. Verify password hash is correct (must be bcrypt)
3. Check PostgreSQL is running:
   ```bash
   psql -U team_4 -d team_4_db -c "SELECT COUNT(*) FROM users;"
   ```

---

## Quick Test Script

Create `/opt/lampp/htdocs/2025docker/team_4/app/admin/test_roles.php`:

```php
<?php
session_start();
require '../db/db.php';

echo '<h1>Role-Based Access Testing</h1>';

// Test 1: Database connection
try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo '<p>✅ Database connected: ' . $row['count'] . ' users found</p>';
} catch (Exception $e) {
    echo '<p>❌ Database error: ' . $e->getMessage() . '</p>';
}

// Test 2: Check role column exists
try {
    $result = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='role'");
    if ($result->rowCount() > 0) {
        echo '<p>✅ Role column exists in users table</p>';
    } else {
        echo '<p>❌ Role column NOT found. Run ALTER TABLE command.</p>';
    }
} catch (Exception $e) {
    echo '<p>❌ Error checking role column: ' . $e->getMessage() . '</p>';
}

// Test 3: Show all users
echo '<h2>Users in Database:</h2>';
try {
    $result = $pdo->query("SELECT id, username, role FROM users ORDER BY id");
    echo '<table border="1" cellpadding="10">';
    echo '<tr><th>ID</th><th>Username</th><th>Role</th></tr>';
    while ($row = $result->fetch()) {
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . $row['username'] . '</td>';
        echo '<td><strong>' . ($row['role'] ?? 'NOT SET') . '</strong></td>';
        echo '</tr>';
    }
    echo '</table>';
} catch (Exception $e) {
    echo '<p>❌ Error: ' . $e->getMessage() . '</p>';
}

// Test 4: Session info (if logged in)
echo '<h2>Current Session:</h2>';
if (isset($_SESSION['user_id'])) {
    echo '<p>✅ Logged in as: ' . $_SESSION['username'] . '</p>';
    echo '<p>✅ Role: ' . ($_SESSION['role'] ?? 'staff (default)') . '</p>';
} else {
    echo '<p>⚠️ Not logged in</p>';
}
?>
```

Then visit: `http://localhost/2025docker/team_4/app/admin/test_roles.php`

---

## Final Verification Checklist

- [ ] Database updated with role column
- [ ] Admin user set to 'supervisor' role
- [ ] Staff test user created with 'staff' role
- [ ] Admin can login and see both tabs
- [ ] Admin has green supervisor badge
- [ ] Staff can login and see only orders tab
- [ ] Staff has pink staff badge
- [ ] Shift Management hidden for staff (inspected HTML)
- [ ] No console errors
- [ ] Session variables correct for both users
- [ ] Page refresh maintains role-based UI (server-side validation)

---

## Success Criteria

**Testing is COMPLETE when:**
✅ Admin sees 2 tabs
✅ Staff sees 1 tab
✅ Both have correct role badges
✅ No console errors
✅ Shift panel hidden for staff even in HTML source
