<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM stories WHERE id = ?");
$stmt->execute([$_GET['id']]);
$story = $stmt->fetch();

if (!$story) {
    die("Story not found!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($story['title']); ?></title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <meta name="theme-color" content="#2563eb">
</head>
<body>
  <div style="max-width:900px;margin:20px auto;padding:0 20px;">
    <a class="admin-login-btn" href="index.php">&larr; Back to Home</a>

    <div class="full-story">
    <h1><?php echo htmlspecialchars($story['title']); ?></h1>
    
    <?php if ($story['image_path']): ?>
      <div class="hero">
        <img src="<?php echo $story['image_path']; ?>" alt="<?php echo htmlspecialchars($story['title']); ?>">
      </div>
    <?php endif; ?>
    
    <div class="story-content">
      <?php echo nl2br(htmlspecialchars($story['content'])); ?>
    </div>
    
    <p class="story-date">Posted on: <?php echo $story['created_at']; ?></p>
  </div>
</body>
</html>