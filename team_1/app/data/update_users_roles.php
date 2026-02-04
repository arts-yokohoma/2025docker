<?php
/**
 * Update users' roles from a CSV file or uploaded file.
 *
 * CSV format (header required):
 *   username,role
 *   admin,admin
 *   manager1,manager
 *   kitchen1,kitchen
 *
 * Role names: admin, manager, kitchen, delivery
 *
 * Usage:
 *   1. Browser: open this file, optionally choose "users_roles.csv" or upload a file.
 *   2. CLI: php update_users_roles.php [path/to/users_roles.csv]
 */

require_once __DIR__ . '/../config/db.php';

$mysqli->set_charset('utf8mb4');

$validRoles = ['admin', 'manager', 'kitchen', 'delivery'];

// Resolve CSV path: CLI arg, then default file in same folder
$csvPath = null;
if (php_sapi_name() === 'cli') {
    $csvPath = $argv[1] ?? __DIR__ . '/users_roles.csv';
} else {
    header('Content-Type: text/html; charset=utf-8');
    if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $csvPath = $_FILES['csv_file']['tmp_name'];
    } elseif (!empty($_POST['use_default']) && file_exists(__DIR__ . '/users_roles.csv')) {
        $csvPath = __DIR__ . '/users_roles.csv';
    } else {
        $csvPath = file_exists(__DIR__ . '/users_roles.csv') ? __DIR__ . '/users_roles.csv' : __DIR__ . '/users_roles_example.csv';
    }
}

$results = [];
$errors = [];

if (!file_exists($csvPath) || !is_readable($csvPath)) {
    $errors[] = 'CSV file not found or not readable: ' . $csvPath;
} else {
    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        $errors[] = 'Could not open CSV file.';
    } else {
        $header = fgetcsv($handle);
        if ($header === false || (count($header) >= 2 && strtolower(trim($header[0])) !== 'username')) {
            $errors[] = 'CSV must have header: username,role';
        } else {
            $roleIds = [];
            foreach ($validRoles as $r) {
                $st = $mysqli->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
                $st->bind_param('s', $r);
                $st->execute();
                $res = $st->get_result();
                if ($row = $res->fetch_assoc()) {
                    $roleIds[$r] = (int) $row['id'];
                }
                $st->close();
            }

            $lineNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                if (count($row) < 2) continue;
                $username = trim($row[0]);
                $role = trim(strtolower($row[1]));
                if ($username === '' || $role === '') continue;

                if (!isset($roleIds[$role])) {
                    $results[] = ['username' => $username, 'role' => $role, 'ok' => false, 'msg' => "Unknown role: $role"];
                    continue;
                }

                $stmt = $mysqli->prepare("UPDATE users SET role_id = ? WHERE username = ?");
                $stmt->bind_param('is', $roleIds[$role], $username);
                if ($stmt->execute()) {
                    $affected = $mysqli->affected_rows;
                    $results[] = [
                        'username' => $username,
                        'role' => $role,
                        'ok' => true,
                        'msg' => $affected > 0 ? 'Updated' : 'No change (username not found or already has this role)'
                    ];
                } else {
                    $results[] = ['username' => $username, 'role' => $role, 'ok' => false, 'msg' => $mysqli->error];
                }
                $stmt->close();
            }
            fclose($handle);
        }
    }
}

if (php_sapi_name() === 'cli') {
    foreach ($errors as $e) echo "[ERROR] $e\n";
    foreach ($results as $r) {
        $s = $r['ok'] ? 'OK' : 'FAIL';
        echo "[$s] {$r['username']} -> {$r['role']}: {$r['msg']}\n";
    }
    exit(empty($errors) ? 0 : 1);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Update users roles - Pizza Mach</title>
    <style>
        body { font-family: sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        .err { color: #c00; }
        .ok { color: #080; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f5f5f5; }
        form { margin: 1rem 0; }
        input[type="file"], button { margin: 4px; }
    </style>
</head>
<body>
    <h1>ユーザーロール一括更新</h1>
    <p>CSV形式: <code>username,role</code>（1行目はヘッダー）。ロール名: admin, manager, kitchen, delivery</p>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv">
        <button type="submit">アップロードして更新</button>
    </form>
    <?php if (file_exists(__DIR__ . '/users_roles.csv')): ?>
    <form method="post">
        <input type="hidden" name="use_default" value="1">
        <button type="submit">users_roles.csv で更新</button>
    </form>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <p class="err"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>

    <?php if (!empty($results)): ?>
        <table>
            <thead>
                <tr><th>username</th><th>role</th><th>結果</th></tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td><?= htmlspecialchars($r['role']) ?></td>
                    <td class="<?= $r['ok'] ? 'ok' : 'err' ?>"><?= htmlspecialchars($r['msg']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="users_roles_example.csv">users_roles_example.csv</a> をダウンロードして編集し、<strong>users_roles.csv</strong> として保存してから「アップロードして更新」または「users_roles.csv で更新」を実行してください。</p>
</body>
</html>
