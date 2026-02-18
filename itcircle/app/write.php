<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image_path = null;

    // Handle image upload (validated via finfo + getimagesize)
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['image']['error'];
            $map = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds server limit.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            $errMsg = isset($map[$code]) ? $map[$code] : 'Unknown upload error.';
            @file_put_contents(__DIR__ . '/logs/app.log', date('c') . " - Upload error ($code): $errMsg" . PHP_EOL, FILE_APPEND);
            $message = '<div class="error-message">Image upload error: ' . htmlspecialchars($errMsg) . '</div>';
        } else {
            $upload_dir = __DIR__ . '/uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $maxSize = 3 * 1024 * 1024; // 3MB
            if ($_FILES['image']['size'] > $maxSize) {
                $message = '<div class="error-message">Image is too large (max 3MB).</div>';
            } else {
                $tmpFile = $_FILES['image']['tmp_name'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpFile);
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                $image_info = @getimagesize($tmpFile);
                if ($image_info === false || !in_array($mime, $allowed)) {
                    $message = '<div class="error-message">Invalid image file. Only JPG, PNG, GIF, and WEBP are allowed.</div>';
                } else {
                    $ext = image_type_to_extension($image_info[2]);
                    $image_name = uniqid('', true) . $ext;
                    $targetPath = $upload_dir . $image_name;
                    $image_path = 'uploads/' . $image_name; // stored path for DB (web relative)

                    if (!move_uploaded_file($tmpFile, $targetPath)) {
                        @file_put_contents(__DIR__ . '/logs/app.log', date('c') . " - move_uploaded_file failed for {$tmpFile} to {$targetPath}" . PHP_EOL, FILE_APPEND);
                        $message = '<div class="error-message">Error saving uploaded image. Check server permissions.</div>';
                    } else {
                        @chmod($targetPath, 0644);
                    }
                }
            }
        }
    }

    if (empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO stories (user_id, title, content, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content, $image_path]);

            $message = '<div class="success-message">✅ Story published successfully!</div>';
            $_POST['title'] = $_POST['content'] = '';

        } catch (PDOException $e) {
            // log the error, but don't show raw DB errors to users
            $logDir = __DIR__ . '/logs/';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            @file_put_contents($logDir . 'app.log', date('c') . " - DB error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            $message = '<div class="error-message">❌ Error saving story. Please try again later.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write New Story - Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#2563eb">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1> Write New Story</h1>
            <div class="admin-nav">
                <a href="index.php">← Back to Site</a>
                <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/logout.php">Logout</a>
            </div>
        </div>

        <div class="write-container">
            <?php echo $message; ?>
            
            <div class="write-form">
                <h2>Create a New Story</h2>
                
                <form method="POST" enctype="multipart/form-data" id="story-form">
                    <div class="form-group">
                        <label for="title">Story Title *</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               placeholder="Enter a captivating title..." required>
                    </div>

                    <div class="form-group">
                        <label for="content">Story Content *</label>
                        <textarea id="content" name="content" rows="12" 
                                  placeholder="Write your amazing story here..." required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <div class="char-counter" id="char-counter">0 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="image">Featured Image (Optional)</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <div class="image-preview" id="image-preview">
                            <img src="" alt="Image preview">
                            <div class="preview-placeholder">Image preview will appear here</div>
                        </div>
                    </div>

                    <button type="submit" class="btn-publish" id="publish-btn">
                        <span id="btn-text">Publish Story</span>
                        <span id="btn-loading" style="display:none;" class="loading"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            const previewImg = preview.querySelector('img');
            const placeholder = preview.querySelector('.preview-placeholder');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    placeholder.style.display = 'none';
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });

        // Character counter
        const contentTextarea = document.getElementById('content');
        const charCounter = document.getElementById('char-counter');
        
        contentTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCounter.textContent = length + ' characters';
            
            if (length > 5000) {
                charCounter.classList.add('warning');
            } else {
                charCounter.classList.remove('warning');
            }
        });

        // Form submission loading animation
        document.getElementById('story-form').addEventListener('submit', function() {
            document.getElementById('btn-text').style.display = 'none';
            document.getElementById('btn-loading').style.display = 'inline-block';
            document.getElementById('publish-btn').disabled = true;
        });
    </script>
</body>
</html>