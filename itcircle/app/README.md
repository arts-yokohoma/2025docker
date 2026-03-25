# Arts Playground

A simple storytelling web app where an administrator can publish short stories with optional featured images. The site includes a public feed, full-story pages, and an admin interface for creating and deleting stories.

Minimal README — quick run and usage notes.

1) Start XAMPP:

```bash
sudo /opt/lampp/lampp start
```

2) Open in browser:

http://localhost/artsplayground

3) Admin panel:

http://localhost/artsplayground/admin/login.php

4) Uploads:

- Images saved to `uploads/` (JPG/PNG/GIF/WEBP). Max ~3MB.
- If upload fails, check `logs/app.log`.

5) Database:

- Create `users` and `stories` tables (see `presentation.html` or ask me for SQL).

Need a simpler change or more help? Tell me what to add or modify.
