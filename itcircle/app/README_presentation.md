Arts Playground — Presentation Notes

Quick start (Linux/XAMPP):

1. Start XAMPP:

```bash
sudo /opt/lampp/lampp start
```

2. Open the site in a browser:

http://localhost/artsplayground

3. Admin panel:

http://localhost/artsplayground/admin/login.php

4. If no admin exists, insert a user (example):

```sql
INSERT INTO users (username, password, is_admin) VALUES ('admin', 'REPLACE_WITH_HASH', 1);
```

Generate a password hash in PHP:

```php
<?php
echo password_hash('yourpassword', PASSWORD_DEFAULT);
```

Presentation suggestions:
- Use `presentation.html` for a single-slide view.
- Use full-screen browser and take screenshots of the home page and admin dashboard.
- Capture the image upload flow via the `write.php` form.

What I changed (high-level):
- Added Google Fonts, modern styles, favicon, and improved admin styling.
- Hardened image uploads (mime check, getimagesize, size limit) and blocked PHP execution in `uploads/`.
- Replaced raw DB error output with server-side logging to `logs/app.log`.
- Fixed and styled `admin/dashboard.php` to show stats and a table of stories.

Next optional steps I can do for you:
- Export a PDF slide from `presentation.html`.
- Create demo admin credentials and seed the DB.
- Record a short screencast of the main flows.

Tell me which you want next and I'll proceed.
