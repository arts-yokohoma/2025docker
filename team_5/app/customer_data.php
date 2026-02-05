<?php
require_once __DIR__ . '/db_config.php';

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
if ($limit <= 0) $limit = 200;
if ($limit > 1000) $limit = 1000;

try {
    $sql = "SELECT * FROM customer ORDER BY created_at DESC NULLS LAST LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>お問い合わせデータ</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="img/nav_bar_logo.png" height="60" class="me-2" alt="Team 5 logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-contact rounded-pill px-4 me-2" href="index.php">ホーム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4" href="admin_panel.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Customer Data (showing up to <?php echo htmlspecialchars($limit); ?> rows)</h2>
            <div>
                <form class="d-flex" method="get" style="gap:8px;">
                    <label class="form-label mb-0">Limit</label>
                    <input name="limit" type="number" class="form-control form-control-sm" value="<?php echo htmlspecialchars($limit); ?>" style="width:100px; margin-left:8px;">
                    <button class="btn btn-sm btn-primary" style="margin-left:8px;">Apply</button>
                </form>
            </div>
        </div>

        <?php if (empty($rows)): ?>
            <div class="alert alert-info">No customer records found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($rows[0]) as $col): ?>
                                <th><?php echo htmlspecialchars($col); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <?php foreach ($r as $v): ?>
                                    <td><?php echo htmlspecialchars((string)$v); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>