<?php
include 'db/connect.php';
header('Content-Type: application/json; charset=utf-8');

// 1. TIMEZONE & DATE SETUP
date_default_timezone_set('Asia/Tokyo');
$today = date('Y-m-d'); 
$currentTime = date('Y-m-d H:i:s');
$displayTime = date('g:i A'); // 12-hour format (e.g., 5:15 AM)

$user_id  = trim($_POST['user_id'] ?? '');
$password = trim($_POST['password'] ?? '');
$action   = trim($_POST['action'] ?? ''); 

if (!$user_id || !$password || !$action) {
    echo json_encode(['status' => 0, 'msg' => '入力データが不足しています']);
    exit;
}

try {
    // Force Database session to Japan Time
    $db->exec("SET TIME ZONE 'Asia/Tokyo'");

    // 2. STAFF AUTHENTICATION
    $stmt = $db->prepare("SELECT id, name, password FROM staff WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff || !password_verify($password, $staff['password'])) {
        echo json_encode(['status' => 0, 'msg' => 'IDまたはパスワードが正しくありません']);
        exit;
    }

    $staff_db_id = $staff['id'];

    // 3. SEQUENCE GUARD (PREVENT MULTIPLE LOGINS)
    $stmt_check = $db->prepare("
        SELECT action_type::text FROM staff_attendance 
        WHERE staff_id = ? 
        AND action_time >= ? AND action_time <= ?
        ORDER BY action_time DESC LIMIT 1
    ");
    $stmt_check->execute([$staff_db_id, "$today 00:00:00", "$today 23:59:59"]);
    $last_action = $stmt_check->fetchColumn();

    if ($action === 'login' && $last_action === 'login') {
        echo json_encode(['status' => 0, 'msg' => "エラー：既に「出勤」済みです。時刻: $displayTime"]);
        exit;
    }
    if ($action === 'logout' && (!$last_action || $last_action === 'logout')) {
        echo json_encode(['status' => 0, 'msg' => "エラー：本日の「出勤」記録がありません。"]);
        exit;
    }

    // 4. RECORD CURRENT ACTION
    $ins = $db->prepare("INSERT INTO staff_attendance (staff_id, action_type, action_time) VALUES (?, ?, ?)");
    $ins->execute([$staff_db_id, $action, $currentTime]);

    $action_names = ['login' => '出勤', 'logout' => '退勤', 'rest_start' => '休憩開始', 'rest_finish' => '休憩終了'];
    $display_action = $action_names[$action] ?? $action;

    // 5. CALCULATE WORK LOGS (ONLY ON LOGOUT)
    if ($action === 'logout') {
        // Retrieve the Login time for today
        $stmt_login = $db->prepare("
            SELECT action_time FROM staff_attendance 
            WHERE staff_id = ? AND action_type = 'login' 
            AND action_time >= ? AND action_time <= ?
            ORDER BY action_time DESC LIMIT 1
        ");
        $stmt_login->execute([$staff_db_id, "$today 00:00:00", "$today 23:59:59"]);
        $login_time = $stmt_login->fetchColumn();

        if ($login_time) {
            /** * INTELLIGENT REST CALCULATION:
             * This only sums up periods where a 'rest_start' is followed by a 'rest_finish'.
             * If one part is missing, the sum will be 0.
             */
            $rest_stmt = $db->prepare("
                SELECT COALESCE(SUM(EXTRACT(EPOCH FROM (f.action_time - s.action_time))/60), 0) as total_rest_minutes
                FROM staff_attendance s
                JOIN staff_attendance f ON s.staff_id = f.staff_id
                WHERE s.staff_id = ? 
                AND s.action_type = 'rest_start' AND f.action_type = 'rest_finish'
                AND s.action_time >= ? AND f.action_time <= ?
                AND s.action_time < f.action_time
            ");
            $rest_stmt->execute([$staff_db_id, $login_time, $currentTime]);
            $rest_minutes = (float)$rest_stmt->fetchColumn();

            /**
             * POSTGRESQL MATH:
             * (Total Seconds / 3600) - (Rest Minutes / 60)
             * Result is a decimal (e.g., 8.5 for 8h 30m)
             */
            $stmt_work = $db->prepare("
                INSERT INTO work_logs (user_id, login_time, logout_time, rest_time, total_hours) 
                VALUES (?, ?, ?, ?, (EXTRACT(EPOCH FROM (?::timestamp - ?::timestamp))/3600) - (? / 60.0))
            ");
            $stmt_work->execute([
                $user_id, 
                $login_time, 
                $currentTime, 
                $rest_minutes, 
                $currentTime, 
                $login_time, 
                $rest_minutes
            ]);
        }
    }

    echo json_encode([
        'status' => 1, 
        'msg' => "[{$displayTime}] {$display_action}を記録しました。", 
        'name' => $staff['name']
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 0, 'msg' => 'システムエラーが発生しました']);
}