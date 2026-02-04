<?php
/**
 * Admin area entry point — redirects to login or admin panel based on session.
 * Access: /team_1/admin/ or /team_1/admin/index.php
 */
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: admin.php');
} else {
    header('Location: login.php');
}
exit;
