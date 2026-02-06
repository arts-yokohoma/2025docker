# Test Users Seed Data

This directory contains scripts to populate your database with test users for development and testing purposes.

## Files

- **`seed_test_users.sql`** - SQL file with INSERT statements for test users
- **`seed_users.php`** - PHP script to execute the SQL file and show results

## Test Users Created

### Total: 18 Test Users

| Role | Count | Usernames | Password |
|------|-------|-----------|----------|
| 👑 **Admin** | 3 | `test_admin1`, `test_admin2`, `test_admin3` | `password123` |
| 👔 **Manager** | 4 | `test_manager1` to `test_manager4` | `password123` |
| 👨‍🍳 **Kitchen** | 5 | `test_kitchen1` to `test_kitchen5` | `password123` |
| 🚗 **Driver** | 6 | `test_driver1` to `test_driver6` | `password123` |

## Usage

### Option 1: Using PHP Script (Recommended)

1. **Via Browser:**
   ```
   http://localhost/your-project/team_1/app/data/seed_users.php
   ```

2. **Via Command Line:**
   ```bash
   php team_1/app/data/seed_users.php
   ```

### Option 2: Using SQL File Directly

If you prefer to run the SQL directly in phpMyAdmin or MySQL CLI:

1. **phpMyAdmin:**
   - Open phpMyAdmin
   - Select your database
   - Go to "Import" tab
   - Choose `seed_test_users.sql`
   - Click "Go"

2. **MySQL CLI:**
   ```bash
   mysql -u your_username -p your_database < team_1/app/data/seed_test_users.sql
   ```

## Login Examples

After seeding, you can log in with these credentials:

```
Username: test_admin1
Password: password123
Role: Admin
```

```
Username: test_manager1
Password: password123
Role: Manager
```

```
Username: test_kitchen1
Password: password123
Role: Kitchen Staff
```

```
Username: test_driver1
Password: password123
Role: Delivery Driver
```

## User Details

All test users have:
- 📧 Email: `{username}@test.com`
- 📞 Phone: `090-XXXX-YYYY` (unique per user)
- 🔐 Password: `password123` (bcrypt hashed)
- 🇯🇵 Japanese names (name + surname)
- 🕐 Auto-generated timestamps (created_at, updated_at)

## Removing Test Users

To remove all test users, run this SQL:

```sql
DELETE FROM users WHERE username LIKE 'test_%';
```

Or uncomment the DELETE line at the top of `seed_test_users.sql` before running it.

## Password Hash

All passwords are hashed using bcrypt (cost 10):
```
Plain: password123
Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

## Notes

- Usernames and emails must be unique (enforced by database)
- If you run the script multiple times with existing users, you'll get duplicate key errors
- To re-seed, first delete the test users or modify usernames
- These users are for **testing only** - do not use in production!

## Verification

After seeding, verify users were created:

```sql
SELECT 
    u.username,
    u.email,
    u.name,
    u.surname,
    r.name as role_name
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username LIKE 'test_%'
ORDER BY r.id, u.username;
```

You should see 18 test users across 4 different roles.
