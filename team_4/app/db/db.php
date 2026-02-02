<?php
/**
 * Database configuration - PostgreSQL
 * 
 * Automatically detects environment (local XAMPP vs Docker/Remote server)
 * and uses appropriate credentials
 */

// Local XAMPP credentials (default)
$localHost = 'localhost';
$localPort = '5432';
$localUser = 'postgres';
$localPass = '';
$localDb   = 'team_4_db';

// Remote server credentials (team 4)
$remoteHost = 'team_4_db';
$remotePort = '5432';
$remoteUser = 'team_4';
$remotePass = 'team4pass';
$remoteDb   = 'team_4_db';

// Detect environment by HTTP host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);

// Select credentials based on environment
if ($isLocal) {
    $DB_HOST = $localHost;
    $DB_PORT = $localPort;
    $DB_USER = $localUser;
    $DB_PASS = $localPass;
    $DB_NAME = $localDb;
} else {
    $DB_HOST = $remoteHost;
    $DB_PORT = $remotePort;
    $DB_USER = $remoteUser;
    $DB_PASS = $remotePass;
    $DB_NAME = $remoteDb;
}

// Allow environment variable overrides
$DB_HOST = getenv('DB_HOST') ?: $DB_HOST;
$DB_PORT = getenv('DB_PORT') ?: $DB_PORT;
$DB_NAME = getenv('DB_NAME') ?: $DB_NAME;
$DB_USER = getenv('DB_USER') ?: $DB_USER;
$DB_PASS = getenv('DB_PASS') ?: $DB_PASS;

// DSN for PDO with pgsql driver
$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Log full error for server logs
    error_log('DB connection error: ' . $e->getMessage());

    // When debugging is enabled (set SHOW_DB_ERROR=true or DEBUG=true), show the PDO error message
    $showDebug = filter_var(getenv('SHOW_DB_ERROR') ?: getenv('DEBUG'), FILTER_VALIDATE_BOOLEAN);
    http_response_code(500);
    if ($showDebug) {
        echo 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    } else {
        echo 'Database connection failed.';
    }
    exit;
}
/**
 * Check if pizza shop can accept new orders based on staff availability
 * Returns array with 'can_accept_orders' boolean and 'message' string
 */
function checkOrderCapacity() {
    global $pdo;
    
    if (!isset($pdo)) {
        return [
            'can_accept_orders' => false,
            'message' => 'Database connection error'
        ];
    }
    
    try {
        $currentTime = date('H:i:s');
        $currentDate = date('Y-m-d');
        $currentHour = intval(date('H'));
        
        // Determine if it's morning or evening shift
        $shiftType = ($currentHour >= 8 && $currentHour < 16) ? 'morning' : 'evening';
        
        // Check if there's an active shift for today
        $stmt = $pdo->prepare("
            SELECT id, staff_count, current_orders, max_orders_per_hour, shift_type
            FROM staff_shifts
            WHERE shift_date = ? 
            AND shift_type = ?
            AND is_active = true
            LIMIT 1
        ");
        
        $stmt->execute([$currentDate, $shiftType]);
        $shift = $stmt->fetch();
        
        // If no shift scheduled for this time
        if (!$shift) {
            return [
                'can_accept_orders' => false,
                'message' => 'Sorry! We are currently closed. No staff scheduled for orders.'
            ];
        }
        
        // Check if staff count is 0
        if ($shift['staff_count'] <= 0) {
            return [
                'can_accept_orders' => false,
                'message' => 'Sorry! We are currently closed. No delivery staff available.'
            ];
        }
        
        // Check if we're at capacity
        $maxCapacity = $shift['staff_count'] * $shift['max_orders_per_hour'];
        if ($shift['current_orders'] >= $maxCapacity) {
            return [
                'can_accept_orders' => false,
                'message' => 'Sorry! We are at maximum capacity. Please try again later.'
            ];
        }
        
        // Orders can be accepted
        return [
            'can_accept_orders' => true,
            'message' => 'Order accepted successfully!'
        ];
        
    } catch (Exception $e) {
        error_log("checkOrderCapacity error: " . $e->getMessage());
        return [
            'can_accept_orders' => false,
            'message' => 'Unable to verify capacity. Please try again.'
        ];
    }
}
?>