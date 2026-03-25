<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Stats
$total_stories = 0;
try {
  $stmt = $pdo->query("SELECT COUNT(*) AS c FROM stories");
  $row = $stmt->fetch();
  $total_stories = $row ? (int)$row['c'] : 0;
} catch (Exception $e) {
  $total_stories = 0;
}

// Fetch stories for table
$stories = [];
try {
  $stmt = $pdo->query("SELECT id, title, created_at FROM stories ORDER BY created_at DESC");
  $stories = $stmt->fetchAll();
} catch (Exception $e) {
  $stories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="admin-container">
  <div class="admin-header">
    <h1>Admin Dashboard</h1>
    <div class="admin-nav">
      <a href="../write.php" class="btn-new">Create New Post</a>
      <a href="logout.php" class="btn">Logout</a>
    </div>
  </div>

  <div class="dashboard-stats">
    <div class="stat-card">
      <h3>Total Stories</h3>
      <div class="number"><?php echo $total_stories; ?></div>
    </div>
  </div>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="success-message" style="max-width:900px;margin:10px auto;">Story deleted successfully.</div>
  <?php elseif (isset($_GET['error'])): ?>
    <div class="error-message" style="max-width:900px;margin:10px auto;"><?php echo htmlspecialchars($_GET['error']); ?></div>
  <?php endif; ?>

  <div class="admin-table">
    <h2>All Stories</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($stories)): ?>
        <tr><td colspan="4">No stories found.</td></tr>
      <?php else: ?>
        <?php foreach ($stories as $story): ?>
          <tr>
            <td><?php echo (int)$story['id']; ?></td>
            <td><?php echo htmlspecialchars($story['title']); ?></td>
            <td><?php echo htmlspecialchars($story['created_at']); ?></td>
            <td>
              <a class="btn btn-view" href="../fullstory.php?id=<?php echo (int)$story['id']; ?>">View</a>
              <a class="btn btn-delete" href="delete.php?id=<?php echo (int)$story['id']; ?>" onclick="return confirm('Delete this story?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>