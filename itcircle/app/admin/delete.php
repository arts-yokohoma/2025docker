<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$log = function($m){ @file_put_contents(__DIR__ . '/../logs/app.log', date('c') . " - delete.php: " . $m . PHP_EOL, FILE_APPEND); };

if (!isset($_GET['id'])) {
    header('Location: dashboard.php?error=' . urlencode('Missing id'));
    exit();
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header('Location: dashboard.php?error=' . urlencode('Invalid id'));
    exit();
}

try {
    // fetch story to remove image
    $stmt = $pdo->prepare('SELECT image_path FROM stories WHERE id = ?');
    $stmt->execute([$id]);
    $story = $stmt->fetch();

    if (!$story) {
        header('Location: dashboard.php?error=' . urlencode('Story not found'));
        exit();
    }

    // delete DB row
    $del = $pdo->prepare('DELETE FROM stories WHERE id = ?');
    $del->execute([$id]);

    // remove image file if present and safe
    if (!empty($story['image_path'])) {
        $img = $story['image_path'];
        // allow only paths inside uploads/
        if (strpos($img, 'uploads/') === 0) {
            $full = __DIR__ . '/../' . $img;
            if (is_file($full)) {
                @unlink($full);
                $log('Unlinked image: ' . $full);
            }
        }
    }

    $log('Deleted story id=' . $id);
    header('Location: dashboard.php?deleted=1');
    exit();
} catch (Exception $e) {
    $log('Error deleting story id=' . $id . ' - ' . $e->getMessage());
    header('Location: dashboard.php?error=' . urlencode('Failed to delete'));
    exit();
}

