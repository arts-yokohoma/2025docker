<?php
require_once 'includes/config.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Arts Playground</title>
 <link rel="stylesheet" href="./css/styles.css">
 <link rel="icon" href="/favicon.svg" type="image/svg+xml">
 <meta name="theme-color" content="#2563eb">
</head>
<body>
  <div class="header">
    <img src="./logo.svg" alt="Arts Playground Logo" class="logo">
   
    <p>Post</p>
  </div>

  <div class="posts">
    <?php
    
    $stmt = $pdo->query("SELECT * FROM stories ORDER BY created_at DESC");
    $stories = $stmt->fetchAll();

    if (empty($stories)) {
      echo '<p>No stories yet.</p>';
    } else {
      foreach ($stories as $story) {
        echo '
        <div class="post-card" onclick="location.href=\'fullstory.php?id=' . $story['id'] . '\'">
          ' . ($story['image_path'] ? '<img src="' . $story['image_path'] . '" alt="' . htmlspecialchars($story['title']) . '">' : '') . '
          <div class="post-content">
            <h2>' . htmlspecialchars($story['title']) . '</h2>
            <p>' . substr(htmlspecialchars($story['content']), 0, 100) . '...</p>
          </div>
        </div>';
      }
    }
    ?>
  </div>

</body>
</html>