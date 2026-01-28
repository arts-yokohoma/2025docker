<?php

/**
 * Database Configuration
 * Supports both localhost and Docker environments
 */

// Determine if running in Docker by checking for Docker IP
$docker_ip = '192.168.20.37';
$local_host = 'localhost';

// Check which host to use
$host = getenv('DB_HOST') ?: (gethostname() === 'localhost' ? $local_host : $docker_ip);

// If the connection fails to Docker IP, fallback to localhost
$port = "5432";
$dbname = "team_4_db";
$user = "team_4"; 
$password = "team4pass";

$pdo = null;

// Try connecting to the configured host first
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, try alternative host
    $fallback_host = ($host === $docker_ip) ? $local_host : $docker_ip;
    
    try {
        $pdo = new PDO("pgsql:host=$fallback_host;port=$port;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e2) {
        // If both fail, try postgres user as fallback
        try {
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", "postgres", "postgres");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e3) {
            die("Database connection failed: " . $e3->getMessage());
        }
    }
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