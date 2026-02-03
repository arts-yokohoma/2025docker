# 🎯 Testing Overview & Quick Links

## 📚 Documentation Files Created

| File | Purpose | Read Time |
|------|---------|-----------|
| **QUICK_TEST.md** | ⭐ **START HERE** - 12 minute test checklist | 3 min |
| **TESTING_GUIDE.md** | Detailed step-by-step testing with screenshots | 15 min |
| **ROLE_IMPLEMENTATION_SUMMARY.md** | What was implemented and how | 10 min |
| **ROLE_BASED_ACCESS_GUIDE.md** | Complete setup and technical details | 15 min |
| **SETUP_ROLES_QUICK.md** | Quick database setup instructions | 5 min |

---

## 🚀 Fastest Way to Test (10 minutes)

### Step 1: Update Database
```bash
psql -U team_4 -d team_4_db
```

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff';
UPDATE users SET role = 'supervisor' WHERE username = 'admin';
INSERT INTO users (username, password, role) 
VALUES ('staff_test', '$2y$12$K4ATy0oQm4XqKYlhv.xnwOb7U./uYj6EJMlz88FVD7R6.p9d3dBCm', 'staff');
SELECT * FROM users;
\q
```

### Step 2: Test Admin Login
- Visit: `http://localhost/2025docker/team_4/app/admin/login.php`
- Username: `admin` / Password: `admin123`
- **Verify:** See both `📋 Shift` and `📦 Orders` tabs + green badge

### Step 3: Test Staff Login
- Logout and login as `staff_test` / `password123`
- **Verify:** See only `📦 Orders` tab + pink badge

### Step 4: Verify Hidden
- Press F12 → Ctrl+F → search "shifts-tab"
- Should show: `display: none;`

---

## 🎨 Expected Visual Results

### Admin (Supervisor) Dashboard
```
┌────────────────────────────────────────────────────┐
│ Pizza Sales Dashboard      👤 admin  👨‍💼 Supervisor  │
├────────────────────────────────────────────────────┤
│ [📋 Shift Management] [📦 Orders Management]       │
├────────────────────────────────────────────────────┤
│                                                    │
│ Shift Management Content Visible...                │
│ - Date selector                                    │
│ - Staff sliders (morning/evening)                  │
│ - Capacity calculator                             │
│                                                    │
└────────────────────────────────────────────────────┘
```

### Staff Dashboard
```
┌────────────────────────────────────────────────────┐
│ Pizza Sales Dashboard   👤 staff_test  👷 Staff    │
├────────────────────────────────────────────────────┤
│ [📦 Orders Management]                             │
├────────────────────────────────────────────────────┤
│                                                    │
│ Orders Management Content Visible...               │
│ - Order filters                                    │
│ - Orders table                                     │
│ - Order summary cards                             │
│                                                    │
│ (Shift Management NOT VISIBLE)                     │
└────────────────────────────────────────────────────┘
```

---

## 🔍 Test Credentials

### For Admin (Supervisor)
```
Username: admin
Password: admin123
Expected: 2 tabs + Green supervisor badge
```

### For Staff
```
Username: staff_test
Password: password123
Expected: 1 tab + Pink staff badge
```

---

## ✅ Test Checklist

### Database Setup
- [ ] Connect to PostgreSQL
- [ ] Run ALTER TABLE command
- [ ] Update admin to supervisor
- [ ] Create staff_test user
- [ ] Verify users table has role column

### Admin Login
- [ ] Can login with admin/admin123
- [ ] See username in header
- [ ] See green supervisor badge
- [ ] See Shift Management tab ✅
- [ ] See Orders Management tab ✅
- [ ] Can click between tabs

### Staff Login
- [ ] Can login with staff_test/password123
- [ ] See username in header
- [ ] See pink staff badge
- [ ] Do NOT see Shift Management tab ❌
- [ ] See Orders Management tab ✅
- [ ] Orders tab is active by default

### Security Verification
- [ ] Inspect HTML - shows `display: none` for shifts
- [ ] Page refresh maintains role restriction
- [ ] Session has correct role value
- [ ] No console errors

---

## 🧪 Testing Flow Diagram

```
Start
  │
  ├─→ Update Database
  │     ├─ ALTER TABLE
  │     ├─ UPDATE admin role
  │     └─ INSERT staff user
  │
  ├─→ Test Admin Login
  │     ├─ Login admin/admin123
  │     ├─ Verify 2 tabs visible
  │     └─ Verify green badge
  │
  ├─→ Test Staff Login
  │     ├─ Logout previous
  │     ├─ Login staff_test/password123
  │     ├─ Verify 1 tab visible
  │     └─ Verify pink badge
  │
  ├─→ Verify Security
  │     ├─ Check HTML (F12)
  │     ├─ Check session data
  │     └─ Reload page
  │
  └─→ ✅ Testing Complete!
```

---

## 📊 Test Results Table

| Scenario | Expected | How to Verify |
|----------|----------|---------------|
| Admin sees Shift tab | ✅ YES | Tab visible in UI |
| Admin sees Orders tab | ✅ YES | Tab visible in UI |
| Admin badge color | 🟢 Green | Visual inspection |
| Staff sees Shift tab | ❌ NO | Inspect HTML - display:none |
| Staff sees Orders tab | ✅ YES | Tab visible in UI |
| Staff badge color | 🔴 Pink | Visual inspection |
| Session role stored | ✅ YES | Check debug.php or console |

---

## 🆘 Quick Troubleshooting

| Issue | Fix |
|-------|-----|
| "Role column not found" | Run ALTER TABLE command again |
| Login fails | Verify user exists in database |
| Both tabs show for staff | Restart Apache: `sudo systemctl restart apache2` |
| Badge not showing | Hard refresh: Ctrl+Shift+R |
| Error on dashboard | Check browser console (F12) |

---

## 📖 Recommended Reading Order

1. **QUICK_TEST.md** ← Start here (fastest)
2. **TESTING_GUIDE.md** ← Detailed steps with screenshots
3. **ROLE_IMPLEMENTATION_SUMMARY.md** ← Understand what changed
4. Other guides as needed for specific topics

---

## ⏱️ Time Estimates

| Task | Time |
|------|------|
| Database setup | 2 min |
| Admin login test | 2 min |
| Staff login test | 2 min |
| Security verification | 2 min |
| Troubleshooting (if needed) | 5-10 min |
| **Total** | **~10 min** |

---

## 🎉 Success!

You'll know testing is successful when:
- ✅ Admin sees 2 tabs with green badge
- ✅ Staff sees 1 tab with pink badge  
- ✅ No errors on page or console
- ✅ Role-based UI works as designed

---

**Next:** Start with QUICK_TEST.md for hands-on testing!
