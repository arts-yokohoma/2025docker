<?php
// Simple upload test (no auth) — logs to logs/app.log
$log = function($msg) { @file_put_contents(__DIR__ . '/logs/app.log', date('c') . " - " . $msg . PHP_EOL, FILE_APPEND); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log('Received POST for test_upload');
    if (!isset($_FILES['file'])) {
        $log('No file in \$_FILES');
        echo "No file uploaded.";
        exit;
    }

    $f = $_FILES['file'];
    $log('file error: ' . $f['error']);
    $log('file size: ' . $f['size']);
    $log('tmp_name: ' . $f['tmp_name']);

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $log('Upload error code: ' . $f['error']);
        echo 'Upload error: ' . htmlspecialchars($f['error']);
        exit;
    }

    $uploads = __DIR__ . '/uploads/';
    if (!is_dir($uploads)) { mkdir($uploads, 0755, true); }

    $target = $uploads . basename($f['name']);
    $log('Attempting move to: ' . $target);
    if (move_uploaded_file($f['tmp_name'], $target)) {
        @chmod($target, 0644);
        $log('move_uploaded_file: success');
        echo 'OK: saved to uploads/' . htmlspecialchars(basename($f['name']));
    } else {
        $log('move_uploaded_file: FAILED');
        echo 'FAILED to move uploaded file. Check permissions.';
    }
    exit;
}

?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Upload Test</title></head>
<body>
<h2>Upload Test</h2>
<form method="post" enctype="multipart/form-data">
<input type="file" name="file" required>
<button type="submit">Upload</button>
</form>
<p>After testing, check logs at <code>logs/app.log</code>.</p>
</body>
</html>
