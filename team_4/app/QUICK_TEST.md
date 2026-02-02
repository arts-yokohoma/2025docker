# ⚡ Quick Test Checklist

## 1️⃣ Prepare Database (5 minutes)

```bash
# Connect to PostgreSQL
psql -U team_4 -d team_4_db
```

```sql
-- Copy-paste this entire block:
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff';
UPDATE users SET role = 'supervisor' WHERE username = 'admin';
INSERT INTO users (username, password, role) 
VALUES ('staff_test', '$2y$12$K4ATy0oQm4XqKYlhv.xnwOb7U./uYj6EJMlz88FVD7R6.p9d3dBCm', 'staff');

-- Verify:
SELECT id, username, role FROM users;
```

**Expected:**
```
 id |  username  |   role
----+------------+----------
  1 | admin      | supervisor
  2 | staff_test | staff
```

Type `\q` to exit

---

## 2️⃣ Test Admin (Supervisor) Login (3 minutes)

1. Visit: `http://localhost/2025docker/team_4/app/admin/login.php`
2. Enter:
   - Username: `admin`
   - Password: `admin123`
3. Click **Login**

✅ **Verify:**
- [ ] Header shows: `👤 admin` + `👨‍💼 Supervisor` (green badge)
- [ ] See 2 tabs: **📋 Shift Management** and **📦 Orders Management**
- [ ] Both tabs clickable
- [ ] Can switch between them

**Screenshots:** Take one of the dashboard showing both tabs

---

## 3️⃣ Test Staff Login (3 minutes)

1. Click **Logout** in top right
2. Visit: `http://localhost/2025docker/team_4/app/admin/login.php`
3. Enter:
   - Username: `staff_test`
   - Password: `password123`
4. Click **Login**

✅ **Verify:**
- [ ] Header shows: `👤 staff_test` + `👷 Staff` (pink badge)
- [ ] See ONLY 1 tab: **📦 Orders Management**
- [ ] Shift Management tab NOT visible
- [ ] Orders tab is active

**Screenshots:** Take one of the dashboard showing only orders tab

---

## 4️⃣ Verify Hidden (1 minute)

While logged in as staff:
1. Press `F12` to open Developer Tools
2. Press `Ctrl+F` to search
3. Search for: `shifts-tab`
4. Should find: `style="display: none;"`

This confirms shift management is hidden even in HTML

---

## 5️⃣ Verify Session (1 minute)

Create file: `/opt/lampp/htdocs/2025docker/team_4/app/admin/debug.php`

```php
<?php session_start(); echo '<pre>'; print_r($_SESSION); echo '</pre>'; ?>
```

**Test 1 - Admin:**
- Login as admin
- Visit: `http://localhost/2025docker/team_4/app/admin/debug.php`
- Should show: `[role] => supervisor`

**Test 2 - Staff:**
- Logout and login as staff_test
- Visit: `http://localhost/2025docker/team_4/app/admin/debug.php`
- Should show: `[role] => staff`

---

## ✅ Test Complete When:

- [ ] Admin sees 2 tabs with green supervisor badge
- [ ] Staff sees 1 tab with pink staff badge
- [ ] HTML source shows `display: none` for staff
- [ ] Session debug shows correct role for each user
- [ ] No console errors

---

## Credentials for Testing

| User | Username | Password | Expected Tabs |
|------|----------|----------|---------------|
| Supervisor | `admin` | `admin123` | 📋 Shift + 📦 Orders |
| Staff | `staff_test` | `password123` | 📦 Orders only |

---

## If Something Wrong:

| Problem | Solution |
|---------|----------|
| Column not found | Run `ALTER TABLE` command again |
| Login fails | Check psql users exist with `SELECT * FROM users;` |
| Both tabs show for staff | Restart Apache: `sudo systemctl restart apache2` |
| Badge not showing | Hard refresh: `Ctrl+Shift+R` |
| Different icon/styling | CSS loading issue, clear cache |

---

**Time to complete: ~12 minutes** ⏱️

Done! 🎉
