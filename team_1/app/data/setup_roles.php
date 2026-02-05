<?php
/**
 * Setup roles table and insert default roles
 *
 * Use this page if you get "Table 'team_1_db.roles' doesn't exist".
 * Run once in the browser: .../team_1/app/data/setup_roles.php
 *
 * - Creates the roles table if it doesn't exist
 * - Inserts admin, manager, kitchen, delivery if missing
 * Safe to run multiple times.
 */

require_once __DIR__ . '/../config/db.php';

$mysqli->set_charset('utf8mb4');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Roles setup - Pizza Mach</title>
    <style>
        body { font-family: sans-serif; max-width: 560px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        .ok { color: #0a0; }
        .err { color: #c00; }
        pre { background: #f5f5f5; padding: 1rem; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Roles セットアップ</h1>

<?php
$ok = true;

// 1) Create roles table if not exists
$createTable = "
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if ($mysqli->query($createTable)) {
    echo "<p class='ok'>✅ roles テーブルを作成または確認しました。</p>\n";
} else {
    echo "<p class='err'>❌ テーブル作成エラー: " . htmlspecialchars($mysqli->error) . "</p>\n";
    $ok = false;
}

if ($ok) {
    // 2) Insert roles used by the app (admin, manager, kitchen, delivery)
    $rolesToAdd = ['admin', 'manager', 'kitchen', 'delivery'];
    echo "<p>ロールを追加しています...</p>\n<pre>\n";

    foreach ($rolesToAdd as $name) {
        $stmt = $mysqli->prepare("INSERT IGNORE INTO roles (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo ($mysqli->affected_rows > 0 ? "✅ Added: $name\n" : "ℹ️  Already exists: $name\n");
        } else {
            echo "❌ Error for $name: " . $mysqli->error . "\n";
        }
        $stmt->close();
    }

    echo "\n</pre>\n";

    // 3) List current roles
    $res = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
    if ($res) {
        echo "<p><strong>現在のロール一覧:</strong></p>\n<pre>\n";
        while ($row = $res->fetch_assoc()) {
            echo "  {$row['id']}: {$row['name']}\n";
        }
        echo "</pre>\n";
        $res->free();
    }
}

if ($ok) {
    echo "<p class='ok'><strong>🎉 セットアップ完了。</strong> <a href='../admin/login.php'>管理画面ログイン</a></p>\n";
} else {
    echo "<p class='err'>エラーが発生しました。config/db.php の接続先を確認してください。</p>\n";
}
?>

</body>
</html>
